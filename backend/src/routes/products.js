import express from 'express';
import { getPool } from '../db.js';
import { requireAuth } from '../middleware/auth.js';

const router = express.Router();

const baseSelect = `
  SELECT p.id, p.title, p.slug, p.subtitle, p.ribbon, p.summary, p.description,
         p.price, p.discount_price, p.stock, p.tags, p.weight_kg, p.allow_personalization,
         p.category_id, p.subcategory_id,
         GROUP_CONCAT(DISTINCT CONCAT(c.id, ':', c.name) ORDER BY c.name SEPARATOR '|') AS categories,
         GROUP_CONCAT(DISTINCT CONCAT(sc.id, ':', sc.name) ORDER BY sc.name SEPARATOR '|') AS subcategories,
         GROUP_CONCAT(DISTINCT CONCAT(va.name, ':', vv.value) ORDER BY va.name, vv.value SEPARATOR '|') AS variations,
         GROUP_CONCAT(DISTINCT vv.id ORDER BY vv.id SEPARATOR ',') AS variation_value_ids_raw,
         GROUP_CONCAT(DISTINCT CONCAT(pi.id, ':', pi.is_primary) ORDER BY pi.is_primary DESC, pi.id ASC SEPARATOR ',') AS image_meta,
         GROUP_CONCAT(DISTINCT pi.image_url ORDER BY pi.is_primary DESC, pi.id ASC SEPARATOR ',') AS images,
         GROUP_CONCAT(DISTINCT pr.related_product_id ORDER BY pr.related_product_id SEPARATOR ',') AS related_products
  FROM products p
  LEFT JOIN product_categories pc ON pc.product_id = p.id
  LEFT JOIN categories c ON pc.category_id = c.id
  LEFT JOIN product_subcategories psc ON psc.product_id = p.id
  LEFT JOIN subcategories sc ON psc.subcategory_id = sc.id
  LEFT JOIN product_images pi ON pi.product_id = p.id
  LEFT JOIN product_variations pv ON pv.product_id = p.id
  LEFT JOIN variation_values vv ON pv.variation_value_id = vv.id
  LEFT JOIN variation_attributes va ON vv.variation_attribute_id = va.id
  LEFT JOIN product_related pr ON pr.product_id = p.id
`;

function parseDelimited(raw, separator = ',') {
  if (!raw) return [];
  return String(raw)
    .split(separator)
    .map((part) => part.trim())
    .filter(Boolean);
}

function parseIdNamePairs(raw) {
  return parseDelimited(raw, '|').map((pair) => {
    const [id, name] = pair.split(':');
    return { id: Number(id), name: name || '' };
  });
}

function parseImageMeta(rawMeta, rawUrls) {
  const urls = parseDelimited(rawUrls);
  const meta = parseDelimited(rawMeta).map((entry) => {
    const [id, isPrimary] = entry.split(':');
    return { id: Number(id), is_primary: isPrimary === '1' };
  });

  return urls.map((url, index) => ({
    id: meta[index]?.id ?? null,
    image_url: url,
    is_primary: meta[index]?.is_primary ?? index === 0
  }));
}

function normalizeProduct(row) {
  const variationValueIds = parseDelimited(row.variation_value_ids_raw).map((id) => Number(id));
  const relatedProductIds = parseDelimited(row.related_products).map((id) => Number(id));

  return {
    ...row,
    categories_list: parseIdNamePairs(row.categories),
    subcategories_list: parseIdNamePairs(row.subcategories),
    variation_value_ids: variationValueIds,
    images_list: parseImageMeta(row.image_meta, row.images),
    related_product_ids: relatedProductIds
  };
}

function toNumericArray(value) {
  if (!Array.isArray(value)) return [];
  return value
    .map((item) => Number(item))
    .filter((num) => Number.isInteger(num) && num > 0);
}

async function resetLinks(connection, table, column, productId, values) {
  await connection.execute(`DELETE FROM ${table} WHERE product_id = ?`, [productId]);
  for (const value of values) {
    await connection.execute(`INSERT IGNORE INTO ${table} (product_id, ${column}) VALUES (?, ?)`, [productId, value]);
  }
}

async function insertImages(connection, productId, images = []) {
  if (!images.length) return;

  const hasPrimary = images.some((image) => image && image.is_primary);
  let primarySet = false;

  for (const [index, image] of images.entries()) {
    if (!image || !image.image_url) continue;
    const isPrimary = image.is_primary || (!hasPrimary && index === 0);
    if (isPrimary && !primarySet) {
      await connection.execute('UPDATE product_images SET is_primary = 0 WHERE product_id = ?', [productId]);
      primarySet = true;
    }
    await connection.execute(
      'INSERT INTO product_images (product_id, image_url, is_primary) VALUES (?, ?, ?)',
      [productId, image.image_url, isPrimary ? 1 : 0]
    );
  }
}

router.get('/', async (req, res) => {
  const db = getPool();
  const { category, subcategory, search } = req.query;
  const filters = [];
  const params = [];
  if (category) {
    filters.push(
      'EXISTS (SELECT 1 FROM product_categories pc_filter WHERE pc_filter.product_id = p.id AND pc_filter.category_id = ?)'
    );
    params.push(category);
  }
  if (subcategory) {
    filters.push(
      'EXISTS (SELECT 1 FROM product_subcategories psc_filter WHERE psc_filter.product_id = p.id AND psc_filter.subcategory_id = ?)'
    );
    params.push(subcategory);
  }
  if (search) {
    filters.push('(p.title LIKE ? OR p.tags LIKE ?)');
    params.push(`%${search}%`, `%${search}%`);
  }
  const where = filters.length ? `WHERE ${filters.join(' AND ')}` : '';
  const [rows] = await db.query(
    `${baseSelect}
     ${where}
     GROUP BY p.id
     ORDER BY p.created_at DESC`,
    params
  );
  return res.json(rows.map(normalizeProduct));
});

router.get('/:id', async (req, res) => {
  const db = getPool();
  const [rows] = await db.query(
    `${baseSelect}
     WHERE p.id = ?
     GROUP BY p.id`,
    [req.params.id]
  );
  if (!rows.length) {
    return res.status(404).json({ message: 'Product not found' });
  }
  return res.json(normalizeProduct(rows[0]));
});

router.post('/', requireAuth, async (req, res) => {
  const db = getPool();
  const {
    title,
    slug,
    subtitle,
    ribbon,
    summary,
    description,
    price,
    discount_price,
    stock,
    tags,
    weight_kg,
    allow_personalization,
    category_id,
    subcategory_id,
    categories,
    subcategories,
    variation_value_ids,
    related_product_ids,
    images
  } = req.body;
  if (!title || !slug || !price) {
    return res.status(400).json({ message: 'Title, slug and price are required' });
  }

  const categoryList = toNumericArray(Array.isArray(categories) ? categories : category_id !== undefined ? [category_id] : []);
  const subcategoryList = toNumericArray(
    Array.isArray(subcategories) ? subcategories : subcategory_id !== undefined ? [subcategory_id] : []
  );
  const variationList = toNumericArray(Array.isArray(variation_value_ids) ? variation_value_ids : []);
  const relatedList = toNumericArray(Array.isArray(related_product_ids) ? related_product_ids : []);

  const connection = await db.getConnection();

  try {
    await connection.beginTransaction();

    const [result] = await connection.execute(
      `INSERT INTO products (
        title, slug, subtitle, ribbon, summary, description, price, discount_price, stock, tags, weight_kg,
        allow_personalization, category_id, subcategory_id
      )
      VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)`,
      [
        title,
        slug,
        subtitle || '',
        ribbon || '',
        summary || '',
        description || '',
        price,
        discount_price ?? null,
        stock || 0,
        tags || '',
        weight_kg ?? null,
        allow_personalization ? 1 : 0,
        categoryList[0] || null,
        subcategoryList[0] || null
      ]
    );

    const productId = result.insertId;

    if (categoryList.length) {
      await resetLinks(connection, 'product_categories', 'category_id', productId, categoryList);
    }
    if (subcategoryList.length) {
      await resetLinks(connection, 'product_subcategories', 'subcategory_id', productId, subcategoryList);
    }
    if (variationList.length) {
      await resetLinks(connection, 'product_variations', 'variation_value_id', productId, variationList);
    }
    if (relatedList.length) {
      const filtered = relatedList.filter((id) => id !== productId);
      if (filtered.length) {
        await resetLinks(connection, 'product_related', 'related_product_id', productId, filtered);
      }
    }
    if (Array.isArray(images)) {
      await insertImages(connection, productId, images);
    }

    await connection.commit();
    return res.status(201).json({ id: productId });
  } catch (err) {
    await connection.rollback();
    return res.status(500).json({ message: 'Failed to create product', detail: err.message });
  } finally {
    connection.release();
  }
});

router.put('/:id', requireAuth, async (req, res) => {
  const db = getPool();
  const {
    title,
    slug,
    subtitle,
    ribbon,
    summary,
    description,
    price,
    discount_price,
    stock,
    tags,
    weight_kg,
    allow_personalization,
    category_id,
    subcategory_id,
    categories,
    subcategories,
    variation_value_ids,
    related_product_ids,
    images
  } = req.body;

  if (!title || !slug || !price) {
    return res.status(400).json({ message: 'Title, slug and price are required' });
  }

  const categoryList = toNumericArray(Array.isArray(categories) ? categories : category_id !== undefined ? [category_id] : []);
  const subcategoryList = toNumericArray(
    Array.isArray(subcategories) ? subcategories : subcategory_id !== undefined ? [subcategory_id] : []
  );
  const variationList = toNumericArray(Array.isArray(variation_value_ids) ? variation_value_ids : []);
  const relatedList = toNumericArray(Array.isArray(related_product_ids) ? related_product_ids : []);

  const connection = await db.getConnection();

  try {
    await connection.beginTransaction();

    const [existing] = await connection.execute('SELECT id FROM products WHERE id = ?', [req.params.id]);
    if (!existing.length) {
      await connection.rollback();
      return res.status(404).json({ message: 'Product not found' });
    }

    await connection.execute(
      `UPDATE products SET
        title = ?, slug = ?, subtitle = ?, ribbon = ?, summary = ?, description = ?,
        price = ?, discount_price = ?, stock = ?, tags = ?, weight_kg = ?, allow_personalization = ?,
        category_id = ?, subcategory_id = ?
      WHERE id = ?`,
      [
        title,
        slug,
        subtitle || '',
        ribbon || '',
        summary || '',
        description || '',
        price,
        discount_price ?? null,
        stock || 0,
        tags || '',
        weight_kg ?? null,
        allow_personalization ? 1 : 0,
        categoryList[0] || null,
        subcategoryList[0] || null,
        req.params.id
      ]
    );

    await resetLinks(connection, 'product_categories', 'category_id', req.params.id, categoryList);
    await resetLinks(connection, 'product_subcategories', 'subcategory_id', req.params.id, subcategoryList);
    await resetLinks(connection, 'product_variations', 'variation_value_id', req.params.id, variationList);

    const filteredRelated = relatedList.filter((id) => id !== Number(req.params.id));
    await resetLinks(connection, 'product_related', 'related_product_id', req.params.id, filteredRelated);

    if (Array.isArray(images)) {
      await connection.execute('DELETE FROM product_images WHERE product_id = ?', [req.params.id]);
      await insertImages(connection, req.params.id, images);
    }

    await connection.commit();
    return res.json({ id: Number(req.params.id), updated: true });
  } catch (err) {
    await connection.rollback();
    return res.status(500).json({ message: 'Failed to update product', detail: err.message });
  } finally {
    connection.release();
  }
});

router.delete('/:id', requireAuth, async (req, res) => {
  const db = getPool();
  const [result] = await db.execute('DELETE FROM products WHERE id = ?', [req.params.id]);
  if (!result.affectedRows) {
    return res.status(404).json({ message: 'Product not found' });
  }
  return res.json({ success: true });
});

router.post('/:id/images', requireAuth, async (req, res) => {
  const db = getPool();
  const { image_url, is_primary } = req.body;
  if (!image_url) {
    return res.status(400).json({ message: 'Image URL is required' });
  }
  if (is_primary) {
    await db.execute('UPDATE product_images SET is_primary = 0 WHERE product_id = ?', [req.params.id]);
  }
  const [result] = await db.execute(
    'INSERT INTO product_images (product_id, image_url, is_primary) VALUES (?, ?, ?)',
    [req.params.id, image_url, is_primary ? 1 : 0]
  );
  return res.status(201).json({ id: result.insertId });
});

router.delete('/:id/images/:imageId', requireAuth, async (req, res) => {
  const db = getPool();
  const [result] = await db.execute('DELETE FROM product_images WHERE product_id = ? AND id = ?', [
    req.params.id,
    req.params.imageId
  ]);
  if (!result.affectedRows) {
    return res.status(404).json({ message: 'Image not found' });
  }
  return res.json({ success: true });
});

export default router;

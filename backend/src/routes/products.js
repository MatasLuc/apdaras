import express from 'express';
import { getPool } from '../db.js';
import { requireAuth } from '../middleware/auth.js';

const router = express.Router();

const baseSelect = `
  SELECT p.id, p.title, p.slug, p.summary, p.description, p.price, p.stock, p.tags,
         p.category_id, p.subcategory_id,
         c.name AS category_name, sc.name AS subcategory_name,
         GROUP_CONCAT(pi.image_url ORDER BY pi.is_primary DESC SEPARATOR ',') AS images
  FROM products p
  LEFT JOIN categories c ON p.category_id = c.id
  LEFT JOIN subcategories sc ON p.subcategory_id = sc.id
  LEFT JOIN product_images pi ON pi.product_id = p.id
`;

router.get('/', async (req, res) => {
  const db = getPool();
  const { category, subcategory, search } = req.query;
  const filters = [];
  const params = [];
  if (category) {
    filters.push('p.category_id = ?');
    params.push(category);
  }
  if (subcategory) {
    filters.push('p.subcategory_id = ?');
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
  return res.json(rows);
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
  return res.json(rows[0]);
});

router.post('/', requireAuth, async (req, res) => {
  const db = getPool();
  const { title, slug, summary, description, price, stock, tags, category_id, subcategory_id } = req.body;
  if (!title || !slug || !price) {
    return res.status(400).json({ message: 'Title, slug and price are required' });
  }
  const [result] = await db.execute(
    `INSERT INTO products (title, slug, summary, description, price, stock, tags, category_id, subcategory_id)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)`,
    [title, slug, summary || '', description || '', price, stock || 0, tags || '', category_id || null, subcategory_id || null]
  );
  return res.status(201).json({ id: result.insertId });
});

router.put('/:id', requireAuth, async (req, res) => {
  const db = getPool();
  const { title, slug, summary, description, price, stock, tags, category_id, subcategory_id } = req.body;
  const [result] = await db.execute(
    `UPDATE products SET title = ?, slug = ?, summary = ?, description = ?, price = ?, stock = ?, tags = ?,
      category_id = ?, subcategory_id = ?
     WHERE id = ?`,
    [title, slug, summary, description, price, stock, tags, category_id, subcategory_id, req.params.id]
  );
  if (!result.affectedRows) {
    return res.status(404).json({ message: 'Product not found' });
  }
  return res.json({ success: true });
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

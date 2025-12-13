import express from 'express';
import { getPool } from '../db.js';
import { requireAuth } from '../middleware/auth.js';

const router = express.Router();

function handleError(res, err, fallbackMessage) {
  // eslint-disable-next-line no-console
  console.error(fallbackMessage, err.message);
  return res.status(500).json({ message: fallbackMessage, detail: err.message });
}

router.get('/', async (_req, res) => {
  try {
    const db = getPool();
    const [rows] = await db.query(
      `SELECT c.id, c.name, c.slug,
              sc.id AS subcategory_id, sc.name AS subcategory_name, sc.slug AS subcategory_slug
       FROM categories c
       LEFT JOIN subcategories sc ON sc.category_id = c.id
       ORDER BY c.name, sc.name`
    );
    return res.json(rows);
  } catch (err) {
    return handleError(res, err, 'Nepavyko užkrauti kategorijų');
  }
});

router.post('/', requireAuth, async (req, res) => {
  try {
    const db = getPool();
    const { name, slug } = req.body;
    if (!name || !slug) {
      return res.status(400).json({ message: 'Category name and slug are required' });
    }
    const [result] = await db.execute('INSERT INTO categories (name, slug) VALUES (?, ?)', [name, slug]);
    return res.status(201).json({ id: result.insertId, name, slug });
  } catch (err) {
    return handleError(res, err, 'Nepavyko sukurti kategorijos');
  }
});

router.put('/:categoryId', requireAuth, async (req, res) => {
  try {
    const db = getPool();
    const { name, slug } = req.body;
    const { categoryId } = req.params;

    if (!name || !slug) {
      return res.status(400).json({ message: 'Category name and slug are required' });
    }

    const [result] = await db.execute('UPDATE categories SET name = ?, slug = ? WHERE id = ?', [name, slug, categoryId]);
    if (!result.affectedRows) {
      return res.status(404).json({ message: 'Category not found' });
    }

    return res.json({ id: Number(categoryId), name, slug });
  } catch (err) {
    return handleError(res, err, 'Nepavyko atnaujinti kategorijos');
  }
});

router.delete('/:categoryId', requireAuth, async (req, res) => {
  try {
    const db = getPool();
    const { categoryId } = req.params;

    await db.execute('DELETE FROM categories WHERE id = ?', [categoryId]);
    return res.json({ id: Number(categoryId), deleted: true });
  } catch (err) {
    return handleError(res, err, 'Nepavyko ištrinti kategorijos');
  }
});

router.post('/:categoryId/subcategories', requireAuth, async (req, res) => {
  try {
    const db = getPool();
    const { name, slug } = req.body;
    const { categoryId } = req.params;
    if (!name || !slug) {
      return res.status(400).json({ message: 'Subcategory name and slug are required' });
    }
    const [result] = await db.execute(
      'INSERT INTO subcategories (category_id, name, slug) VALUES (?, ?, ?)',
      [categoryId, name, slug]
    );
    return res.status(201).json({ id: result.insertId, name, slug, category_id: Number(categoryId) });
  } catch (err) {
    return handleError(res, err, 'Nepavyko sukurti subkategorijos');
  }
});

router.put('/:categoryId/subcategories/:subcategoryId', requireAuth, async (req, res) => {
  try {
    const db = getPool();
    const { name, slug } = req.body;
    const { categoryId, subcategoryId } = req.params;

    if (!name || !slug) {
      return res.status(400).json({ message: 'Subcategory name and slug are required' });
    }

    const [result] = await db.execute(
      'UPDATE subcategories SET name = ?, slug = ?, category_id = ? WHERE id = ?',
      [name, slug, categoryId, subcategoryId]
    );

    if (!result.affectedRows) {
      return res.status(404).json({ message: 'Subcategory not found' });
    }

    return res.json({ id: Number(subcategoryId), name, slug, category_id: Number(categoryId) });
  } catch (err) {
    return handleError(res, err, 'Nepavyko atnaujinti subkategorijos');
  }
});

router.delete('/:categoryId/subcategories/:subcategoryId', requireAuth, async (req, res) => {
  try {
    const db = getPool();
    const { subcategoryId } = req.params;

    await db.execute('DELETE FROM subcategories WHERE id = ?', [subcategoryId]);
    return res.json({ id: Number(subcategoryId), deleted: true });
  } catch (err) {
    return handleError(res, err, 'Nepavyko ištrinti subkategorijos');
  }
});

export default router;

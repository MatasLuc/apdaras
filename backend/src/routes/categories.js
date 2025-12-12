import express from 'express';
import { getPool } from '../db.js';
import { requireAuth } from '../middleware/auth.js';

const router = express.Router();

router.get('/', async (_req, res) => {
  const db = getPool();
  const [rows] = await db.query(
    `SELECT c.id, c.name, c.slug,
            sc.id AS subcategory_id, sc.name AS subcategory_name, sc.slug AS subcategory_slug
     FROM categories c
     LEFT JOIN subcategories sc ON sc.category_id = c.id
     ORDER BY c.name, sc.name`
  );
  return res.json(rows);
});

router.post('/', requireAuth, async (req, res) => {
  const db = getPool();
  const { name, slug } = req.body;
  if (!name || !slug) {
    return res.status(400).json({ message: 'Category name and slug are required' });
  }
  const [result] = await db.execute('INSERT INTO categories (name, slug) VALUES (?, ?)', [name, slug]);
  return res.status(201).json({ id: result.insertId, name, slug });
});

router.post('/:categoryId/subcategories', requireAuth, async (req, res) => {
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
});

export default router;

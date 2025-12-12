import express from 'express';
import { getPool } from '../db.js';
import { requireAuth } from '../middleware/auth.js';

const router = express.Router();

router.get('/', async (_req, res) => {
  const db = getPool();
  const [rows] = await db.query('SELECT * FROM shipping_methods ORDER BY id');
  return res.json(rows);
});

router.post('/', requireAuth, async (req, res) => {
  const db = getPool();
  const { name, price, estimated_days } = req.body;
  if (!name || price === undefined) {
    return res.status(400).json({ message: 'Name and price are required' });
  }
  const [result] = await db.execute(
    'INSERT INTO shipping_methods (name, price, estimated_days) VALUES (?, ?, ?)',
    [name, price, estimated_days || null]
  );
  return res.status(201).json({ id: result.insertId });
});

router.put('/:id', requireAuth, async (req, res) => {
  const db = getPool();
  const { name, price, estimated_days } = req.body;
  const [result] = await db.execute(
    'UPDATE shipping_methods SET name = ?, price = ?, estimated_days = ? WHERE id = ?',
    [name, price, estimated_days || null, req.params.id]
  );
  if (!result.affectedRows) {
    return res.status(404).json({ message: 'Shipping method not found' });
  }
  return res.json({ success: true });
});

router.delete('/:id', requireAuth, async (req, res) => {
  const db = getPool();
  const [result] = await db.execute('DELETE FROM shipping_methods WHERE id = ?', [req.params.id]);
  if (!result.affectedRows) {
    return res.status(404).json({ message: 'Shipping method not found' });
  }
  return res.json({ success: true });
});

export default router;

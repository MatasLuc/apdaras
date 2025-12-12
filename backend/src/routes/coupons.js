import express from 'express';
import { getPool } from '../db.js';
import { requireAuth } from '../middleware/auth.js';

const router = express.Router();

router.get('/', async (_req, res) => {
  const db = getPool();
  const [rows] = await db.query('SELECT * FROM coupons ORDER BY id DESC');
  return res.json(rows);
});

router.post('/', requireAuth, async (req, res) => {
  const db = getPool();
  const { code, discount_type, discount_value, expires_at, usage_limit } = req.body;
  if (!code || !discount_type || !discount_value) {
    return res.status(400).json({ message: 'Code, discount_type and discount_value are required' });
  }
  const [result] = await db.execute(
    `INSERT INTO coupons (code, discount_type, discount_value, expires_at, usage_limit)
     VALUES (?, ?, ?, ?, ?)`,
    [code, discount_type, discount_value, expires_at || null, usage_limit || null]
  );
  return res.status(201).json({ id: result.insertId });
});

router.put('/:id', requireAuth, async (req, res) => {
  const db = getPool();
  const { code, discount_type, discount_value, expires_at, usage_limit, times_used } = req.body;
  const [result] = await db.execute(
    `UPDATE coupons SET code = ?, discount_type = ?, discount_value = ?, expires_at = ?, usage_limit = ?, times_used = ?
     WHERE id = ?`,
    [code, discount_type, discount_value, expires_at, usage_limit, times_used || 0, req.params.id]
  );
  if (!result.affectedRows) {
    return res.status(404).json({ message: 'Coupon not found' });
  }
  return res.json({ success: true });
});

router.delete('/:id', requireAuth, async (req, res) => {
  const db = getPool();
  const [result] = await db.execute('DELETE FROM coupons WHERE id = ?', [req.params.id]);
  if (!result.affectedRows) {
    return res.status(404).json({ message: 'Coupon not found' });
  }
  return res.json({ success: true });
});

export default router;

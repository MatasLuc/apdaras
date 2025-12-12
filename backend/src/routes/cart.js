import express from 'express';
import { getPool } from '../db.js';
import { requireAuth } from '../middleware/auth.js';

const router = express.Router();

router.get('/', requireAuth, async (req, res) => {
  const db = getPool();
  const [rows] = await db.query(
    `SELECT ci.id, ci.product_id, ci.quantity, p.title, p.price
     FROM cart_items ci
     JOIN products p ON ci.product_id = p.id
     WHERE ci.user_id = ?`,
    [req.user.id]
  );
  return res.json(rows);
});

router.post('/', requireAuth, async (req, res) => {
  const db = getPool();
  const { product_id, quantity } = req.body;
  if (!product_id) {
    return res.status(400).json({ message: 'product_id is required' });
  }
  const [result] = await db.execute(
    `INSERT INTO cart_items (user_id, product_id, quantity) VALUES (?, ?, ?)
     ON DUPLICATE KEY UPDATE quantity = VALUES(quantity)`
    ,
    [req.user.id, product_id, quantity || 1]
  );
  return res.status(201).json({ id: result.insertId });
});

router.delete('/:id', requireAuth, async (req, res) => {
  const db = getPool();
  const [result] = await db.execute('DELETE FROM cart_items WHERE id = ? AND user_id = ?', [req.params.id, req.user.id]);
  if (!result.affectedRows) {
    return res.status(404).json({ message: 'Cart item not found' });
  }
  return res.json({ success: true });
});

export default router;

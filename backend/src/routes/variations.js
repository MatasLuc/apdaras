import express from 'express';
import { getPool } from '../db.js';
import { requireAuth } from '../middleware/auth.js';

const router = express.Router();

router.get('/', async (_req, res) => {
  const db = getPool();
  const [rows] = await db.query(
    `SELECT va.id AS attribute_id, va.name AS attribute_name,
            vv.id AS value_id, vv.value
     FROM variation_attributes va
     LEFT JOIN variation_values vv ON vv.variation_attribute_id = va.id
     ORDER BY va.name, vv.value`
  );

  const attributes = [];
  const grouped = new Map();

  for (const row of rows) {
    if (!grouped.has(row.attribute_id)) {
      const entry = { id: row.attribute_id, name: row.attribute_name, values: [] };
      grouped.set(row.attribute_id, entry);
      attributes.push(entry);
    }
    if (row.value_id) {
      grouped.get(row.attribute_id).values.push({ id: row.value_id, value: row.value });
    }
  }

  return res.json(attributes);
});

router.post('/attributes', requireAuth, async (req, res) => {
  const db = getPool();
  const { name } = req.body;
  if (!name) {
    return res.status(400).json({ message: 'Variation attribute name is required' });
  }

  const [result] = await db.execute('INSERT INTO variation_attributes (name) VALUES (?)', [name]);
  return res.status(201).json({ id: result.insertId, name });
});

router.post('/attributes/:attributeId/values', requireAuth, async (req, res) => {
  const db = getPool();
  const { value } = req.body;
  const { attributeId } = req.params;

  if (!value) {
    return res.status(400).json({ message: 'Variation value is required' });
  }

  const [result] = await db.execute(
    'INSERT INTO variation_values (variation_attribute_id, value) VALUES (?, ?)',
    [attributeId, value]
  );

  return res.status(201).json({ id: result.insertId, value, variation_attribute_id: Number(attributeId) });
});

export default router;

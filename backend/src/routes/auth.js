import express from 'express';
import bcrypt from 'bcrypt';
import jwt from 'jsonwebtoken';
import { getPool } from '../db.js';

const router = express.Router();

router.post('/register', async (req, res) => {
  const db = getPool();
  const { email, password, name } = req.body;
  if (!email || !password || !name) {
    return res.status(400).json({ message: 'Email, name and password are required' });
  }

  const [existing] = await db.execute('SELECT id FROM users WHERE email = ?', [email]);
  if (existing.length) {
    return res.status(409).json({ message: 'User already exists' });
  }

  const hash = await bcrypt.hash(password, Number(process.env.BCRYPT_ROUNDS || 10));
  const [result] = await db.execute(
    'INSERT INTO users (email, password_hash, name) VALUES (?, ?, ?)',
    [email, hash, name]
  );

  return res.status(201).json({ id: result.insertId, email, name });
});

router.post('/login', async (req, res) => {
  const db = getPool();
  const { email, password } = req.body;
  if (!email || !password) {
    return res.status(400).json({ message: 'Email and password are required' });
  }

  const [rows] = await db.execute('SELECT id, password_hash, role, name FROM users WHERE email = ?', [email]);
  if (!rows.length) {
    return res.status(401).json({ message: 'Invalid credentials' });
  }

  const user = rows[0];
  const match = await bcrypt.compare(password, user.password_hash);
  if (!match) {
    return res.status(401).json({ message: 'Invalid credentials' });
  }

  const token = jwt.sign({ id: user.id, role: user.role, email, name: user.name }, process.env.JWT_SECRET, {
    expiresIn: '7d'
  });
  return res.json({ token });
});

export default router;

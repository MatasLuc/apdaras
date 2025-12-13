import express from 'express';
import fs from 'fs/promises';
import path from 'path';
import { requireAuth } from '../middleware/auth.js';

const router = express.Router();
const uploadDir = path.join(process.cwd(), 'upload');

async function ensureUploadDir() {
  await fs.mkdir(uploadDir, { recursive: true });
}

router.post('/', requireAuth, async (req, res) => {
  const { fileName, dataUrl } = req.body || {};

  if (!fileName || !dataUrl) {
    return res.status(400).json({ message: 'fileName ir dataUrl yra privalomi' });
  }

  const match = String(dataUrl).match(/^data:(.*?);base64,(.*)$/);
  if (!match) {
    return res.status(400).json({ message: 'Neteisingas failo formatas' });
  }

  const mime = match[1] || 'application/octet-stream';
  const buffer = Buffer.from(match[2], 'base64');

  try {
    await ensureUploadDir();
    const safeName = fileName.replace(/[^a-zA-Z0-9_.-]/g, '_');
    const finalName = `${Date.now()}-${safeName}`;
    const targetPath = path.join(uploadDir, finalName);
    await fs.writeFile(targetPath, buffer);

    const absoluteUrl = `${req.protocol}://${req.get('host')}/upload/${finalName}`;
    return res.status(201).json({ url: absoluteUrl, size: buffer.length, mime });
  } catch (err) {
    return res.status(500).json({ message: 'Nepavyko įrašyti failo', detail: err.message });
  }
});

export default router;

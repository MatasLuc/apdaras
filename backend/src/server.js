import express from 'express';
import cors from 'cors';
import dotenv from 'dotenv';
import fs from 'fs/promises';
import path from 'path';
import { initDb } from './db.js';
import authRoutes from './routes/auth.js';
import categoryRoutes from './routes/categories.js';
import productRoutes from './routes/products.js';
import couponRoutes from './routes/coupons.js';
import shippingRoutes from './routes/shipping.js';
import cartRoutes from './routes/cart.js';
import variationRoutes from './routes/variations.js';
import uploadRoutes from './routes/upload.js';

dotenv.config();

const app = express();
app.use(
  cors({
    origin: true,
    credentials: true,
    allowedHeaders: ['Content-Type', 'Authorization', 'X-Admin-Role']
  })
);
app.use(express.json({ limit: '12mb' }));

const uploadDir = path.join(process.cwd(), 'upload');
app.use('/upload', express.static(uploadDir));

app.get('/health', (_req, res) => res.json({ ok: true }));
app.use('/auth', authRoutes);
app.use('/categories', categoryRoutes);
app.use('/products', productRoutes);
app.use('/coupons', couponRoutes);
app.use('/shipping-methods', shippingRoutes);
app.use('/cart', cartRoutes);
app.use('/variations', variationRoutes);
app.use('/upload', uploadRoutes);

const port = process.env.PORT || 4000;

async function startServer() {
  try {
    await initDb();
    await fs.mkdir(uploadDir, { recursive: true });
    app.listen(port, () => {
      console.log(`API klausosi ${port} prievade`);
    });
  } catch (err) {
    const errorMsg = `KLAIDA: ${err.message}\nSTACK: ${err.stack}\n`;
    try {
      await fs.writeFile('API_STARTUP_ERROR.txt', errorMsg);
    } catch (e) {
      console.error('Nepavyko įrašyti klaidos failo');
    }
    process.exit(1);
  }
}

startServer();

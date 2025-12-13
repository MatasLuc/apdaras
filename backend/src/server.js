import express from 'express';
import cors from 'cors';
import dotenv from 'dotenv';
import { initDb } from './db.js';
import authRoutes from './routes/auth.js';
import categoryRoutes from './routes/categories.js';
import productRoutes from './routes/products.js';
import couponRoutes from './routes/coupons.js';
import shippingRoutes from './routes/shipping.js';
import cartRoutes from './routes/cart.js';
import variationRoutes from './routes/variations.js';

dotenv.config();

const app = express();
app.use(cors());
app.use(express.json());

app.get('/health', (_req, res) => res.json({ ok: true }));
app.use('/auth', authRoutes);
app.use('/categories', categoryRoutes);
app.use('/products', productRoutes);
app.use('/coupons', couponRoutes);
app.use('/shipping-methods', shippingRoutes);
app.use('/cart', cartRoutes);
app.use('/variations', variationRoutes);

const port = process.env.PORT || 4000;

async function startServer() {
  try {
    await initDb();
    app.listen(port, () => {
      // eslint-disable-next-line no-console
      console.log(`API klausosi ${port} prievade`);
    });
  } catch (err) {
    // eslint-disable-next-line no-console
    console.error('Nepavyko inicijuoti duomenų bazės:', err.message);
    process.exit(1);
  }
}

startServer();

import jwt from 'jsonwebtoken';

export function requireAuth(req, res, next) {
  const header = req.headers.authorization;
  // Allow either a JWT bearer token or an admin role header from the PHP panel.
  if (header) {
    const [scheme, token] = header.split(' ');
    if (scheme !== 'Bearer' || !token) {
      return res.status(401).json({ message: 'Invalid authorization format' });
    }

    try {
      const payload = jwt.verify(token, process.env.JWT_SECRET);
      req.user = payload;
      return next();
    } catch (err) {
      return res.status(401).json({ message: 'Invalid or expired token' });
    }
  }

  const adminRole = (req.headers['x-admin-role'] || '').toString().toLowerCase();
  if (adminRole === 'admin') {
    req.user = { role: 'admin', source: 'panel' };
    return next();
  }

  return res.status(401).json({ message: 'Authorization header missing' });
}

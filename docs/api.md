# API maršrutai

## Autentifikacija
- `POST /auth/register` – sukuria vartotoją (laukeliai: `email`, `password`, `name`).
- `POST /auth/login` – grąžina JWT, kuris naudojamas su `Authorization: Bearer <token>`.

## Kategorijos
- `GET /categories` – grąžina kategorijų ir subkategorijų medį.
- `POST /categories` – kuria kategoriją (auth required).
- `POST /categories/:categoryId/subcategories` – kuria subkategoriją (auth required).

## Produktai
- `GET /products` – filtrai: `category`, `subcategory`, `search`.
- `GET /products/:slug` – produkto detalė.
- `POST /products` – kuria produktą, priima `images` masyvą `{ url, is_primary }` (auth required).
- `PUT /products/:id` – atnaujina produkto laukus (auth required).
- `DELETE /products/:id` – pašalina produktą (auth required).
- `POST /products/:id/images` – prideda nuotrauką ir pažymi kaip pagrindinę (auth required).
- `DELETE /products/:productId/images/:imageId` – trina nuotrauką (auth required).

## Kuponai
- `GET /coupons` – kuponų sąrašas (auth required).
- `POST /coupons` – kuria kuponą (auth required).
- `POST /coupons/validate` – patikrina kupono galiojimą.

## Pristatymas
- `GET /shipping-methods` – pristatymo būdai.
- `POST /shipping-methods` – sukuria pristatymo būdą (auth required).
- `PUT /shipping-methods/:id` – atnaujina (auth required).
- `DELETE /shipping-methods/:id` – pašalina (auth required).

## Krepšelis
- `GET /cart` – vartotojo krepšelis.
- `POST /cart` – prideda prekę (`product_id`, `quantity`).
- `PUT /cart/:itemId` – keičia kiekį.
- `DELETE /cart/:itemId` – pašalina.

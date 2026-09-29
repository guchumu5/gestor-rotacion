# Gestor de rotación

App web móvil para anotar **quién compra, cuándo y cuánto**. Sirve para ver medias mensuales y hacerse una idea de cuánto producto conviene tener preparado.

Los datos se guardan en el navegador (`localStorage`). No hace falta servidor.

## Cómo usarla

1. Entra con la contraseña de acceso del productor: **hola** (candado local en el navegador, no es un login de servidor).
2. Pulsa **Registrar** y anota cliente, producto, cantidad e **importe total cobrado**.
3. Mira **Resumen** para medias, mejores clientes y mezcla de producto.
4. En **Fichas** puedes renombrar clientes y productos.

Clientes de ejemplo: SanPablo, Mecanico, Fr. Productos de ejemplo: Rojo, Ches, Win.

## Arranque local

```bash
npm install
npm run dev
```

Abre la URL que muestre Vite (normalmente `http://localhost:5173`).

## Compilar para publicar

```bash
npm run build
```

El resultado queda en `dist/`. Puedes servir esa carpeta en cualquier hosting estático.

### GitHub Pages u otro estático

1. Publica el contenido de `dist/` (o conecta Pages al workflow de build).
2. La app usa rutas relativas (`base: './'`), así que también funciona abriendo los ficheros como sitio estático.
3. En iPhone: Safari → Compartir → **Añadir a pantalla de inicio** para usarla como mini-app.

## Notas

- El acceso es una frase compartida en el cliente. Sirve para un uso intranet/local, no sustituye un login de verdad.
- Los datos viven en ese navegador. Si cambias de móvil o borras datos del sitio, se pierde el historial (hasta que haya backend).
- Para resetear, borra los datos de la web en el navegador o la clave `gestor-rotacion:v1` de `localStorage`.

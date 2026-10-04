import assert from 'node:assert/strict';

const base = process.env.LAB6_API_URL || 'http://127.0.0.1:8006/index.php/api';
let token;
let productId;
let count = 0;

async function call(path, method = 'GET', body, bearer = token) {
  const response = await fetch(base + path, {
    method,
    headers: {
      ...(body !== undefined ? { 'Content-Type': 'application/json' } : {}),
      ...(bearer ? { Authorization: `Bearer ${bearer}` } : {}),
    },
    ...(body !== undefined ? { body: JSON.stringify(body) } : {}),
  });
  const text = await response.text();
  let data;
  try { data = JSON.parse(text); }
  catch { throw new Error(`Expected JSON, received ${response.status}: ${text.slice(0, 350)}`); }
  return { status: response.status, data };
}

function check(condition, message) {
  assert.ok(condition, message);
  count++;
  console.log(`PASS ${message}`);
}

try {
  check((await call('')).status === 200, 'API health endpoint');
  for (const [path, method] of [['/products', 'GET'], ['/products', 'POST'], ['/products/1', 'PUT'], ['/products/1', 'PATCH'], ['/products/1', 'DELETE'], ['/logout', 'POST']]) {
    check((await call(path, method, undefined, null)).status === 401, `${method} ${path} requires authentication`);
  }
  const options = await fetch(base + '/products/1', { method: 'OPTIONS', headers: { Origin: 'http://127.0.0.1:5173', 'Access-Control-Request-Method': 'PUT', 'Access-Control-Request-Headers': 'authorization,content-type' } });
  check(options.status === 204 && options.headers.get('access-control-allow-origin') === 'http://127.0.0.1:5173', 'CORS preflight accepts frontend');
  const blockedOrigin = await fetch(base, { headers: { Origin: 'https://unrelated.example' } });
  check(!blockedOrigin.headers.get('access-control-allow-origin'), 'CORS rejects unconfigured origin');
  check((await call('/login', 'POST', { username: 'admin', password: 'wrong' }, null)).status === 401, 'Wrong credentials rejected');
  check((await call('/login', 'POST', { username: "admin' OR 1=1 --", password: 'wrong' }, null)).status === 401, 'SQL injection login rejected');
  const login = await call('/login', 'POST', { username: process.env.LAB6_USERNAME || 'admin', password: process.env.LAB6_PASSWORD || 'admin123' }, null);
  check(login.status === 200 && login.data.token && !JSON.stringify(login.data).includes('password'), 'Login returns token without password');
  token = login.data.token;
  check((await call('/products', 'GET', undefined, token + 'tampered')).status === 401, 'Tampered token rejected');
  const before = await call('/products');
  check(before.status === 200 && Array.isArray(before.data.products), 'Authenticated product list');
  const product = { product_name: 'Lab 6 API Test ' + Date.now(), description: "USB cable & adapter <set>", price: '125.50', quantity: 3 };
  check((await call('/products', 'POST', { ...product, price: '-1', quantity: '1.5' })).status === 422, 'Invalid price and quantity rejected');
  check((await call('/products', 'POST', { ...product, product_name: 'x'.repeat(101) })).status === 422, 'Overlong name rejected');
  check((await call('/products', 'POST', { ...product, product_name: { injected: true } })).status === 422, 'Non-scalar field rejected');
  const added = await call('/products', 'POST', product);
  check(added.status === 201 && added.data.product.id, 'POST creates product');
  productId = added.data.product.id;
  check(added.data.product.description === product.description && added.data.product.created_at, 'Description preserved and timestamp generated');
  const edited = await call(`/products/${productId}`, 'PUT', { ...product, product_name: product.product_name + ' edited', price: '150.00', quantity: 5 });
  check(edited.status === 200 && Number(edited.data.product.price) === 150 && edited.data.product.quantity === 5, 'PUT updates product');
  const patched = await call(`/products/${productId}`, 'PATCH', { quantity: 9 });
  check(patched.status === 200 && patched.data.product.quantity === 9 && patched.data.product.product_name === edited.data.product.product_name, 'PATCH changes only provided fields');
  check((await call(`/products/${productId}`, 'PUT', { quantity: 1 })).status === 422, 'Incomplete PUT rejected');
  check((await call(`/products/${productId}`, 'PATCH', { quantity: '2147483648' })).status === 422, 'Integer overflow rejected');
  const removed = await call(`/products/${productId}`, 'DELETE');
  check(removed.status === 200, 'DELETE removes product');
  check(!(await call('/products')).data.products.some(p => p.id === productId), 'Deleted product absent from list');
  check((await call(`/products/${productId}`, 'DELETE')).status === 404, 'Deleting missing product returns 404');
  check((await call(`/products/${productId}`, 'PATCH', { quantity: 1 })).status === 404, 'Updating missing product returns 404');
  productId = null;
  const migrationRoute = await fetch(base.replace(/\/api$/, '/migrate'));
  check(migrationRoute.status === 404, 'Migration operations are unavailable over HTTP');
  check((await call('/logout', 'POST')).status === 200, 'Logout succeeds');
  check((await call('/products')).status === 401, 'Logged-out token is rejected');
  token = null;
  console.log(`${count} checks passed.`);
} finally {
  if (productId && token) await call(`/products/${productId}`, 'DELETE');
  if (token) await call('/logout', 'POST');
}

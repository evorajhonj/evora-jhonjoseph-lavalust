<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class ApiController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->call->library('api');
    }

    private function body()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!is_array($data) || array_is_list($data)) {
            $this->api->respond_error('Send a JSON object.', 400);
        }
        return $data;
    }

    private function authenticated_user()
    {
        $payload = $this->api->require_jwt();
        if (!isset($payload['sid']) || isset($payload['type'])) {
            $this->api->respond_error('Please log in again.', 401);
        }
        $this->call->database();
        $user = $this->db->raw(
            'SELECT u.id, u.username FROM users u JOIN refresh_tokens r ON r.user_id = u.id
             WHERE u.id = ? AND u.is_active = 1 AND r.jti = ? AND r.expires_at > NOW() LIMIT 1',
            [$payload['sub'], $payload['sid']]
        )->fetch();
        if (!$user) {
            $this->api->respond_error('Please log in again.', 401);
        }
        $this->api->rate_limit('products_' . $user['id']);
        return $payload;
    }

    private function product_data(array $data)
    {
        $errors = [];
        foreach (['product_name', 'description', 'price', 'quantity'] as $field) {
            if (!isset($data[$field]) || !is_scalar($data[$field]) || is_bool($data[$field])) {
                $errors[] = str_replace('_', ' ', ucfirst($field)) . ' is required.';
                $data[$field] = '';
            }
            $data[$field] = trim((string) $data[$field]);
        }
        if (!preg_match('/^.{1,100}$/us', $data['product_name'])) {
            $errors[] = 'Product name must be 1 to 100 characters.';
        }
        if ($data['description'] === '' || strlen($data['description']) > 65535) {
            $errors[] = 'Description is required and must fit in 65535 bytes.';
        }
        if (!preg_match('/^\d{1,8}(\.\d{1,2})?$/', $data['price'])) {
            $errors[] = 'Price must be 0 to 99999999.99 with at most two decimal places.';
        }
        if (!preg_match('/^\d{1,10}$/', $data['quantity']) || (float) $data['quantity'] > 2147483647) {
            $errors[] = 'Quantity must be a non-negative whole number up to 2147483647.';
        }
        if ($errors) {
            $this->api->respond(['error' => implode(' ', array_unique($errors))], 422);
        }
        return [
            'product_name' => $data['product_name'],
            'description' => $data['description'],
            'price' => number_format((float) $data['price'], 2, '.', ''),
            'quantity' => (int) $data['quantity'],
        ];
    }

    public function index()
    {
        $this->api->respond(['message' => 'Lab 6 Product API', 'student' => 'Jhon Joseph Evora']);
    }

    public function preflight()
    {
        $this->api->respond([], 204);
    }

    public function login()
    {
        $this->api->rate_limit('login_' . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'), 10, 60);
        $data = $this->body();
        if (!is_string($data['username'] ?? null) || !is_string($data['password'] ?? null)) {
            $this->api->respond_error('Username and password are required.', 422);
        }
        $this->call->database();
        $user = $this->db->raw('SELECT * FROM users WHERE username = ? LIMIT 1', [trim($data['username'])])->fetch();
        if (!$user || !(int) $user['is_active'] || !password_verify($data['password'], $user['password'])) {
            $this->api->respond_error('Invalid username or password.', 401);
        }
        $tokens = $this->api->issue_tokens($user);
        $session = $this->api->decode_jwt($tokens['refresh_token']);
        $tokens['access_token'] = $this->api->encode_jwt(['sub' => $user['id'], 'sid' => $session['jti']]);
        $this->api->respond([
            'message' => 'Logged in successfully.',
            'token' => $tokens['access_token'],
            'expires_in' => $tokens['expires_in'],
            'user' => ['id' => $user['id'], 'username' => $user['username']],
        ]);
    }

    public function logout()
    {
        $payload = $this->authenticated_user();
        $this->db->raw('DELETE FROM refresh_tokens WHERE user_id = ? AND jti = ?', [$payload['sub'], $payload['sid']]);
        $this->api->respond(['message' => 'Logged out successfully.']);
    }

    public function products()
    {
        $this->authenticated_user();
        $this->call->model('ProductModel');
        $this->api->respond(['products' => $this->ProductModel->all()]);
    }

    public function create()
    {
        $this->authenticated_user();
        $this->call->model('ProductModel');
        $id = $this->ProductModel->insert($this->product_data($this->body()));
        $this->api->respond(['message' => 'Product added.', 'product' => $this->ProductModel->find((int) $id)], 201);
    }

    public function update($id)
    {
        $this->authenticated_user();
        $this->call->model('ProductModel');
        $product = $this->ProductModel->find((int) $id);
        if (!$product) {
            $this->api->respond_error('Product not found.', 404);
        }
        $data = $this->body();
        if ($_SERVER['REQUEST_METHOD'] === 'PATCH') {
            $data = array_replace($product, $data);
        }
        $this->ProductModel->update((int) $id, $this->product_data($data));
        $this->api->respond(['message' => 'Product updated.', 'product' => $this->ProductModel->find((int) $id)]);
    }

    public function delete($id)
    {
        $this->authenticated_user();
        $this->call->model('ProductModel');
        if (!$this->ProductModel->find((int) $id)) {
            $this->api->respond_error('Product not found.', 404);
        }
        $this->ProductModel->delete((int) $id);
        $this->api->respond(['message' => 'Product deleted.']);
    }
}

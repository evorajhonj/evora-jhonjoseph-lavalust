<?php
defined('PREVENT_DIRECT_ACCESS') OR exit('No direct script access allowed');

class MigrationController extends Controller
{
    public function __construct()
    {
        parent::__construct();
        if (PHP_SAPI !== 'cli') {
            show_404();
            exit;
        }
        $this->call->library('migration');
    }

    public function create_migration($migration_class)
    {
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $migration_class)) {
            fwrite(STDERR, "Use a snake_case migration name.\n");
            exit(1);
        }
        $this->migration->create_migration($migration_class);
    }

    public function migrate() { $this->migration->migrate(); }
    public function rollback() { $this->migration->rollback(); }
    public function rollback_all() { $this->migration->rollback_all(); }
    public function refresh() { $this->migration->refresh(); }
    public function status() { $this->migration->status(); }

    public function seed_admin()
    {
        $username = getenv('AUTH_USERNAME') ?: 'admin';
        $password = getenv('AUTH_PASSWORD');
        if (!$password) {
            fwrite(STDERR, "Set AUTH_PASSWORD before creating the login account.\n");
            exit(1);
        }
        if ($this->db->raw('SELECT id FROM users WHERE username = ?', [$username])->fetch()) {
            echo "Login account already exists.\n";
            return;
        }
        $this->db->raw(
            'INSERT INTO users (username, email, password, role, is_active) VALUES (?, ?, ?, ?, 1)',
            [$username, getenv('AUTH_EMAIL') ?: 'evora.jhonj@minsu.edu.ph', password_hash($password, PASSWORD_DEFAULT), 'admin']
        );
        echo "Login account created.\n";
    }
}

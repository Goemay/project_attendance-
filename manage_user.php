<?php
// manage_user.php — Admin-only user management (Tailwind UI)

require_once __DIR__ . '/auth.php';
require_admin(); // blocks non-admins
require_once __DIR__ . '/db.php';

date_default_timezone_set('Asia/Jakarta');
$pdo = get_pdo();

$flash_ok = '';
$flash_err = '';

function only_role(string $r): string {
    $r = strtolower(trim($r));
    return in_array($r, ['admin', 'user'], true) ? $r : 'user';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $token  = $_POST['csrf_token'] ?? '';

    if (!verify_csrf($token)) {
        $flash_err = 'Invalid request token.';
    } else {
        try {
            if ($action === 'create') {
                $name  = trim($_POST['name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $pass  = $_POST['password'] ?? '';
                $role  = only_role($_POST['role'] ?? 'user');

                if ($name === '' || $email === '' || $pass === '') {
                    throw new RuntimeException('Name, email, and password are required.');
                }

                $chk = $pdo->prepare('SELECT 1 FROM users WHERE email = ?');
                $chk->execute([$email]);
                if ($chk->fetchColumn()) {
                    throw new RuntimeException('Email is already registered.');
                }

                $hash = password_hash($pass, PASSWORD_BCRYPT);
                $ins = $pdo->prepare('INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, ?)');
                $ins->execute([$name, $email, $hash, $role]);
                $flash_ok = 'User created.';
            } elseif ($action === 'update') {
                $id    = (int)($_POST['id'] ?? 0);
                $name  = trim($_POST['name'] ?? '');
                $email = trim($_POST['email'] ?? '');
                $role  = only_role($_POST['role'] ?? 'user');
                $pass  = $_POST['password'] ?? '';

                if ($id <= 0 || $name === '' || $email === '') {
                    throw new RuntimeException('Invalid input for update.');
                }

                $chk = $pdo->prepare('SELECT 1 FROM users WHERE email = ? AND id <> ?');
                $chk->execute([$email, $id]);
                if ($chk->fetchColumn()) {
                    throw new RuntimeException('Email is already used by another account.');
                }

                if ($pass !== '') {
                    $hash = password_hash($pass, PASSWORD_BCRYPT);
                    $upd = $pdo->prepare('UPDATE users SET name = ?, email = ?, role = ?, password = ? WHERE id = ?');
                    $upd->execute([$name, $email, $role, $hash, $id]);
                } else {
                    $upd = $pdo->prepare('UPDATE users SET name = ?, email = ?, role = ? WHERE id = ?');
                    $upd->execute([$name, $email, $role, $id]);
                }
                $flash_ok = 'User updated.';
            } elseif ($action === 'delete') {
                $id = (int)($_POST['id'] ?? 0);
                if ($id <= 0) {
                    throw new RuntimeException('Invalid user id.');
                }
                if (!empty($_SESSION['user_id']) && (int)$_SESSION['user_id'] === $id) {
                    throw new RuntimeException('Cannot delete the currently logged-in admin.');
                }
                $del = $pdo->prepare('DELETE FROM users WHERE id = ?');
                $del->execute([$id]);
                $flash_ok = 'User deleted.';
            } else {
                $flash_err = 'Unknown action.';
            }
        } catch (Throwable $e) {
            $flash_err = $e->getMessage();
        }
    }
}

$stmt = $pdo->query('SELECT id, name, email, role, created_at FROM users ORDER BY created_at DESC, id DESC');
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

include __DIR__ . '/includes/header.php'; // Tailwind CDN is loaded here
?>
<main class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 py-6">
  <div class="flex flex-col sm:flex-row sm:items-end sm:justify-between gap-3">
    <div>
      <h1 class="text-2xl font-semibold tracking-tight text-slate-900">Manage Users</h1>
      <p class="text-slate-500 text-sm">Create, edit, or delete accounts.</p>
    </div>
    <div class="flex items-center gap-2">
      <!-- Back button added -->
      <a href="admin.php" class="inline-flex items-center rounded-lg bg-white border border-slate-200 px-3 py-2 text-slate-700 hover:bg-slate-50 shadow-sm">
        ← Back
      </a>
      <button id="btn-open-create" class="inline-flex items-center rounded-lg bg-indigo-600 px-4 py-2 text-white font-semibold shadow-sm hover:bg-indigo-500">
        + New user
      </button>
    </div>
  </div>

  <?php if ($flash_ok): ?>
    <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-2 text-emerald-900 text-sm"><?= htmlspecialchars($flash_ok, ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>
  <?php if ($flash_err): ?>
    <div class="mt-4 rounded-lg border border-rose-200 bg-rose-50 px-4 py-2 text-rose-900 text-sm"><?= htmlspecialchars($flash_err, ENT_QUOTES, 'UTF-8') ?></div>
  <?php endif; ?>

  <div class="mt-6 overflow-x-auto rounded-xl border border-slate-200 bg-white shadow-sm">
    <table class="min-w-full divide-y divide-slate-200">
      <thead class="bg-slate-50">
        <tr class="text-left text-xs font-semibold uppercase tracking-wider text-slate-600">
          <th class="px-4 py-3">ID</th>
          <th class="px-4 py-3">Name</th>
          <th class="px-4 py-3">Email</th>
          <th class="px-4 py-3">Role</th>
          <th class="px-4 py-3">Created</th>
          <th class="px-4 py-3">Actions</th>
        </tr>
      </thead>
      <tbody class="divide-y divide-slate-100 text-sm">
        <?php foreach ($users as $u): ?>
          <tr class="hover:bg-slate-50">
            <td class="px-4 py-3"><?= (int)$u['id'] ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($u['name']) ?></td>
            <td class="px-4 py-3"><?= htmlspecialchars($u['email']) ?></td>
            <td class="px-4 py-3">
              <span class="inline-flex items-center rounded-md px-2 py-0.5 text-xs font-medium
                <?= $u['role']==='admin' ? 'bg-indigo-100 text-indigo-700' : 'bg-slate-100 text-slate-700' ?>">
                <?= htmlspecialchars($u['role']) ?>
              </span>
            </td>
            <td class="px-4 py-3"><?= htmlspecialchars($u['created_at'] ?? '') ?></td>
            <td class="px-4 py-3">
              <div class="flex items-center gap-2">
                <button
                  class="px-3 py-1 rounded-md bg-white border border-slate-200 hover:bg-slate-50"
                  data-edit='<?= htmlspecialchars(json_encode([
                    "id"=>$u['id'],
                    "name"=>$u['name'],
                    "email"=>$u['email'],
                    "role"=>$u['role'],
                  ]), ENT_QUOTES, 'UTF-8') ?>'
                  onclick="openEdit(this)">
                  Edit
                </button>
                <form method="post" onsubmit="return confirm('Delete this user?');">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
                  <input type="hidden" name="action" value="delete">
                  <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                  <button class="px-3 py-1 rounded-md bg-rose-600 text-white hover:bg-rose-500">Delete</button>
                </form>
              </div>
            </td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</main>

<!-- Create modal -->
<div id="modal-create" class="fixed inset-0 z-50 hidden">
  <div class="absolute inset-0 bg-black/30" onclick="closeCreate()"></div>
  <div class="absolute inset-0 flex items-center justify-center p-4">
    <div class="w-full max-w-md rounded-2xl bg-white shadow-xl border border-slate-200">
      <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center">
        <h3 class="text-lg font-semibold text-slate-900">New user</h3>
        <button onclick="closeCreate()" class="text-slate-500 hover:text-slate-800">✕</button>
      </div>
      <form method="post" class="p-5 space-y-4">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="action" value="create">
        <div>
          <label class="block text-sm font-medium text-slate-700">Full name</label>
          <input class="mt-1 block w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                 name="name" required>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700">Email</label>
          <input type="email" class="mt-1 block w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                 name="email" required>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700">Password</label>
            <input type="password" class="mt-1 block w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500"
                   name="password" required>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Role</label>
            <select name="role" class="mt-1 block w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
              <option value="user">User</option>
              <option value="admin">Admin</option>
            </select>
          </div>
        </div>
        <div class="pt-2 flex items-center justify-end gap-2">
          <button type="button" onclick="closeCreate()" class="px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50">Cancel</button>
          <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white font-semibold hover:bg-indigo-500">Create</button>
        </div>
      </form>
    </div>
  </div>
</div>

<!-- Edit modal -->
<div id="modal-edit" class="fixed inset-0 z-50 hidden">
  <div class="absolute inset-0 bg-black/30" onclick="closeEdit()"></div>
  <div class="absolute inset-0 flex items-center justify-center p-4">
    <div class="w-full max-w-md rounded-2xl bg-white shadow-xl border border-slate-200">
      <div class="px-5 py-4 border-b border-slate-100 flex justify-between items-center">
        <h3 class="text-lg font-semibold text-slate-900">Edit user</h3>
        <button onclick="closeEdit()" class="text-slate-500 hover:text-slate-800">✕</button>
      </div>
      <form method="post" class="p-5 space-y-4">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="action" value="update">
        <input type="hidden" id="edit-id" name="id">
        <div>
          <label class="block text-sm font-medium text-slate-700">Full name</label>
          <input id="edit-name" name="name" class="mt-1 block w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500" required>
        </div>
        <div>
          <label class="block text-sm font-medium text-slate-700">Email</label>
          <input id="edit-email" type="email" name="email" class="mt-1 block w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500" required>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
          <div>
            <label class="block text-sm font-medium text-slate-700">New password</label>
            <input type="password" name="password" class="mt-1 block w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500" placeholder="Leave blank to keep current">
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Role</label>
            <select id="edit-role" name="role" class="mt-1 block w-full rounded-lg border-slate-300 focus:border-indigo-500 focus:ring-indigo-500">
              <option value="user">User</option>
              <option value="admin">Admin</option>
            </select>
          </div>
        </div>
        <div class="pt-2 flex items-center justify-end gap-2">
          <button type="button" onclick="closeEdit()" class="px-4 py-2 rounded-lg border border-slate-200 bg-white hover:bg-slate-50">Cancel</button>
          <button class="px-4 py-2 rounded-lg bg-indigo-600 text-white font-semibold hover:bg-indigo-500">Save</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
const modalCreate = document.getElementById('modal-create');
const modalEdit   = document.getElementById('modal-edit');

document.getElementById('btn-open-create')?.addEventListener('click', () => {
  modalCreate.classList.remove('hidden');
});
function closeCreate(){ modalCreate.classList.add('hidden'); }

function openEdit(btn){
  try{
    const data = JSON.parse(btn.getAttribute('data-edit'));
    document.getElementById('edit-id').value    = data.id;
    document.getElementById('edit-name').value  = data.name || '';
    document.getElementById('edit-email').value = data.email || '';
    document.getElementById('edit-role').value  = (data.role || 'user');
    modalEdit.classList.remove('hidden');
  }catch(e){
    alert('Failed to open editor.');
  }
}
function closeEdit(){ modalEdit.classList.add('hidden'); }
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>

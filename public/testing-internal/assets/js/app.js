// ---------------------------------------------------------------------------
// Tiny SPA: hash-based router + fetch wrapper talking to the PHP API.
// No build step, no framework — everything lives in this one file so it's
// easy to see the whole flow. Split it up once the app grows past this.
// ---------------------------------------------------------------------------

const app = document.getElementById('app');
const navLinks = document.getElementById('nav-links');

const state = {
  user: null,       // filled in by loadSession()
  csrfToken: null,  // filled in by loadSession()
};

// ---- API helper -----------------------------------------------------------

async function api(path, { method = 'GET', body } = {}) {
  const headers = { 'Content-Type': 'application/json' };
  if (method !== 'GET' && state.csrfToken) {
    headers['X-CSRF-Token'] = state.csrfToken;
  }

  const res = await fetch(`api/${path}`, {
    method,
    headers,
    credentials: 'same-origin', // send the PHP session cookie
    body: body ? JSON.stringify(body) : undefined,
  });

  const data = await res.json().catch(() => ({}));
  if (!res.ok) throw new Error(data.error || `Request failed (${res.status})`);
  return data;
}

async function loadSession() {
  const [{ csrf_token }, { user }] = await Promise.all([
    api('auth.php?action=csrf-token'),
    api('auth.php?action=me'),
  ]);
  state.csrfToken = csrf_token;
  state.user = user;
}

// ---- Router -----------------------------------------------------------

const routes = {
  '/': viewHome,
  '/login': viewLogin,
  '/signup': viewSignup,
  '/dashboard': viewDashboard,
  '/admin': viewAdmin,
};

function navigate() {
  const path = location.hash.slice(1) || '/';
  const guarded = ['/dashboard', '/admin'];

  if (guarded.includes(path) && !state.user) {
    location.hash = '#/login';
    return;
  }
  if (path === '/admin' && state.user?.role !== 'admin') {
    app.innerHTML = alertBox('You need an admin account to view this page.', 'danger');
    return;
  }

  renderNav();
  (routes[path] || viewNotFound)();
}

window.addEventListener('hashchange', navigate);

// ---- Shared UI bits -----------------------------------------------------------

function alertBox(message, kind = 'danger') {
  return `<div class="alert alert-${kind}">${escapeHtml(message)}</div>`;
}

function escapeHtml(str) {
  const div = document.createElement('div');
  div.textContent = str;
  return div.innerHTML;
}

function renderNav() {
  if (state.user) {
    navLinks.innerHTML = `
      <span class="navbar-text text-light me-2">Hi, ${escapeHtml(state.user.name)}</span>
      <a class="btn btn-outline-light btn-sm" href="#/dashboard">Dashboard</a>
      ${state.user.role === 'admin' ? '<a class="btn btn-outline-light btn-sm" href="#/admin">Admin</a>' : ''}
      <button class="btn btn-light btn-sm" id="logout-btn">Log out</button>
    `;
    document.getElementById('logout-btn').onclick = async () => {
      const { csrf_token } = await api('auth.php?action=logout', { method: 'POST' });
      state.user = null;
      state.csrfToken = csrf_token; // old token died with the session
      location.hash = '#/';
    };
  } else {
    navLinks.innerHTML = `
      <a class="btn btn-outline-light btn-sm" href="#/login">Log in</a>
      <a class="btn btn-light btn-sm" href="#/signup">Sign up</a>
    `;
  }
}

// ---- Views -----------------------------------------------------------

function viewHome() {
  app.innerHTML = `
    <div class="text-center">
      <h1>PHP SPA Template</h1>
      <p class="lead">Login, signup, user dashboard, and admin dashboard — wired up and ready to build on.</p>
    </div>
  `;
}

function viewLogin() {
  app.innerHTML = `
    <div class="row justify-content-center">
      <div class="col-md-5">
        <h2 class="mb-3">Log in</h2>
        <div id="form-msg"></div>
        <form id="login-form">
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" class="form-control" name="password" required>
          </div>
          <button class="btn btn-primary w-100" type="submit">Log in</button>
        </form>
        <p class="mt-3">No account? <a href="#/signup">Sign up</a></p>
      </div>
    </div>
  `;

  document.getElementById('login-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const msg = document.getElementById('form-msg');
    try {
      const { user } = await api('auth.php?action=login', {
        method: 'POST',
        body: { email: fd.get('email'), password: fd.get('password') },
      });
      state.user = user;
      location.hash = '#/dashboard';
    } catch (err) {
      msg.innerHTML = alertBox(err.message);
    }
  });
}

function viewSignup() {
  app.innerHTML = `
    <div class="row justify-content-center">
      <div class="col-md-5">
        <h2 class="mb-3">Sign up</h2>
        <div id="form-msg"></div>
        <form id="signup-form">
          <div class="mb-3">
            <label class="form-label">Name</label>
            <input type="text" class="form-control" name="name" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="email" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Password</label>
            <input type="password" class="form-control" name="password" minlength="8" required>
            <div class="form-text">At least 8 characters.</div>
          </div>
          <button class="btn btn-primary w-100" type="submit">Create account</button>
        </form>
        <p class="mt-3">Already have an account? <a href="#/login">Log in</a></p>
      </div>
    </div>
  `;

  document.getElementById('signup-form').addEventListener('submit', async (e) => {
    e.preventDefault();
    const fd = new FormData(e.target);
    const msg = document.getElementById('form-msg');
    try {
      await api('auth.php?action=signup', {
        method: 'POST',
        body: { name: fd.get('name'), email: fd.get('email'), password: fd.get('password') },
      });
      msg.innerHTML = alertBox('Account created! Redirecting to login…', 'success');
      setTimeout(() => (location.hash = '#/login'), 1200);
    } catch (err) {
      msg.innerHTML = alertBox(err.message);
    }
  });
}

async function viewDashboard() {
  app.innerHTML = `<p>Loading…</p>`;
  try {
    const { user } = await api('dashboard.php');
    app.innerHTML = `
      <h2>Welcome, ${escapeHtml(user.name)}</h2>
      <p class="text-muted">${escapeHtml(user.email)} · role: ${escapeHtml(user.role)}</p>
      <div class="card">
        <div class="card-body">
          This is your dashboard. Replace this card with your app's real content.
        </div>
      </div>
    `;
  } catch (err) {
    app.innerHTML = alertBox(err.message);
  }
}

async function viewAdmin() {
  app.innerHTML = `<p>Loading…</p>`;
  try {
    const { users } = await api('admin.php?action=list-users');
    app.innerHTML = `
      <h2>Admin — Users</h2>
      <table class="table table-striped align-middle">
        <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Joined</th><th></th></tr></thead>
        <tbody>
          ${users.map(rowForUser).join('')}
        </tbody>
      </table>
    `;
    users.forEach((u) => {
      const toggleBtn = document.getElementById(`toggle-${u.id}`);
      const deleteBtn = document.getElementById(`delete-${u.id}`);
      if (toggleBtn) toggleBtn.onclick = () => setRole(u.id, u.role === 'admin' ? 'user' : 'admin');
      if (deleteBtn) deleteBtn.onclick = () => deleteUser(u.id);
    });
  } catch (err) {
    app.innerHTML = alertBox(err.message);
  }
}

function rowForUser(u) {
  const isSelf = u.id === state.user.id;
  return `
    <tr>
      <td>${escapeHtml(u.name)}</td>
      <td>${escapeHtml(u.email)}</td>
      <td><span class="badge bg-${u.role === 'admin' ? 'primary' : 'secondary'}">${u.role}</span></td>
      <td>${escapeHtml(u.created_at)}</td>
      <td class="text-end">
        <button class="btn btn-sm btn-outline-secondary" id="toggle-${u.id}" ${isSelf ? 'disabled' : ''}>
          Make ${u.role === 'admin' ? 'user' : 'admin'}
        </button>
        <button class="btn btn-sm btn-outline-danger" id="delete-${u.id}" ${isSelf ? 'disabled' : ''}>Delete</button>
      </td>
    </tr>
  `;
}

async function setRole(userId, role) {
  try {
    await api('admin.php?action=set-role', { method: 'POST', body: { user_id: userId, role } });
    viewAdmin();
  } catch (err) {
    app.innerHTML = alertBox(err.message) + app.innerHTML;
  }
}

async function deleteUser(userId) {
  if (!confirm('Delete this user? This cannot be undone.')) return;
  try {
    await api('admin.php?action=delete-user', { method: 'POST', body: { user_id: userId } });
    viewAdmin();
  } catch (err) {
    app.innerHTML = alertBox(err.message) + app.innerHTML;
  }
}

function viewNotFound() {
  app.innerHTML = alertBox('Page not found.', 'warning');
}

// ---- Boot -----------------------------------------------------------

(async function init() {
  try {
    await loadSession();
  } catch (err) {
    console.error('Failed to load session:', err);
  }
  navigate();
})();

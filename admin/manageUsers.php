<?php
include('../includes/header.php');
include('../functions/functions.php');

if (isset($_SESSION['auth'])) {
  if ($_SESSION['auth_user']['role'] !== "admin") {
    header('Location: ../index.php');
  }
}else{
  header('Location: ../login.php');
}
?>

<div class="container py-4">

  <?php if(isset($_SESSION['message'])) { ?>
    <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
      <strong>Hey! </strong><?= $_SESSION['message']; ?>.
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php unset($_SESSION['message']); } ?>

  <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h2 class="fw-bold mb-1">Manage Users</h2>
      <p class="text-muted mb-0"><?= getCountNo("users");?> users in your library</p>
    </div>
  </div>

  <!-- FILTER BAR -->
  <div class="card mb-4">
    <div class="card-body p-3">
      <div class="row g-3 align-items-center">

        <!-- SEARCH -->
        <div class="col-md-4">
          <div class="input-group">
            <span class="input-group-text bg-transparent border-end-0">
              <i class="bi bi-search"></i>
            </span>
            <input
              type="search"
              class="form-control border-start-0"
              placeholder="Search users..."
              aria-label="Search"
              data-search-table="usersTable"
            >
          </div>
        </div>

        <!-- EMAIL DOMAIN FILTER -->
        <div class="col-md-4">
          <div class="input-group">
            <span class="input-group-text bg-transparent border-end-0">
              <i class="bi bi-envelope"></i>
            </span>
            <select
              class="form-select border-start-0"
              data-filter-table="usersTable"
              data-filter-selector=".user-email"
              data-filter-mode="endswith"
            >
              <option value="" selected>All Domains</option>
              <option value="@gmail.com">@gmail.com</option>
              <option value="@hotmail.com">@hotmail.com</option>
            </select>
          </div>
        </div>

        <!-- ROLE FILTER -->
        <div class="col-md-4">
          <div class="input-group">
            <span class="input-group-text bg-transparent border-end-0">
              <i class="bi bi-person-badge"></i>
            </span>
            <select
              class="form-select border-start-0"
              data-filter-table="usersTable"
              data-filter-selector=".user-role"
              data-filter-mode="equals"
            >
              <option value="" selected>All Roles</option>
              <option value="client">client</option>
              <option value="admin">admin</option>
            </select>
          </div>
        </div>

      </div>
    </div>
  </div>

  <!-- TABLE -->
  <div class="card">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table
          class="table table-hover align-middle mb-0"
          id="usersTable"
          data-ctable
          data-per-page="8"
          data-pager="usersPagination"
        >
          <thead class="table-light">
            <tr class="text-muted text-center">
              <th>Name</th>
              <th>Email</th>
              <th>Role</th>
              <th class="text-center pe-3">Actions</th>
            </tr>
          </thead>
          <tbody>
            <?php
              $users = getAll("users");
              if($users && mysqli_num_rows($users) > 0){
                foreach ($users as $user) {
            ?>
              <tr class="text-center">
                <td class="user-name"><?= htmlspecialchars($user['name']) ?></td>
                <td class="user-email"><?= htmlspecialchars($user['email']) ?></td>
                <td class="user-role"><?= htmlspecialchars($user['role']) ?></td>
                <td class="text-center pe-3">
                  <button
                    type="button"
                    class="btn btn-sm btn-outline-primary editBtn"
                    data-bs-toggle="modal"
                    data-bs-target="#editUserModal"
                    data-id="<?= (int)$user['id']; ?>"
                  >
                    <i class="bi bi-pencil"></i>
                  </button>

                  <form
                    action="../functions/handleUsers.php"
                    method="post"
                    class="d-inline"
                    onsubmit="return confirm('Are you sure you want to delete this user?')"
                  >
                    <input type="hidden" name="user_id" value="<?= (int)$user['id']; ?>">
                    <button class="btn btn-sm btn-danger ms-2" name="deleteUserBtn">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                </td>
              </tr>
            <?php
                }
              } else {
            ?>
              <tr>
                <td colspan="4" class="text-center text-muted fw-semibold py-4">No results found!</td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <!-- Pagination UI -->
  <nav class="mt-3">
    <ul class="pagination justify-content-center mb-0" id="usersPagination"></ul>
  </nav>

</div>

<!-- Edit User Modal -->
<div class="modal fade" id="editUserModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content shadow-sm" method="POST" action="../functions/handleUsers.php">
      <div class="modal-header">
        <h5 class="modal-title fw-semibold"><i class="bi bi-pencil-square me-1"></i> Edit User</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" name="user_id" id="edit_user_id">

        <div class="mb-3">
          <label class="form-label">Name</label>
          <input type="text" name="name" id="edit_name" class="form-control" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Email</label>
          <input type="text" name="email" id="edit_email" class="form-control" required>
        </div>

        <div class="mb-0">
          <label class="form-label">Role</label>
          <select name="role" id="edit_role" class="form-select" required>
            <option value="" selected disabled>Select Role</option>
            <option value="client">client</option>
            <option value="admin">admin</option>
          </select>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="updateUserBtn" class="btn btn-success">
          <i class="bi bi-check2 me-1"></i> Save Changes
        </button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const normalize = (s) => (s || '').toLowerCase();

  const tables = document.querySelectorAll('table[data-ctable]');
  tables.forEach((table) => {
    const tbody = table.querySelector('tbody');
    if (!tbody) return;

    const tableId = table.id;

    const perPageAttr = parseInt(table.dataset.perPage, 10);
    const perPage = Number.isFinite(perPageAttr) && perPageAttr > 0 ? perPageAttr : 10;

    const pagerId = table.dataset.pager;
    const pager = pagerId ? document.getElementById(pagerId) : null;

    const rows = Array.from(tbody.querySelectorAll('tr'));

    let noRow = rows.find(tr => {
      const td = tr.querySelector('td[colspan]');
      return td && normalize(td.innerText).includes('no results');
    }) || null;

    const colCount =
      table.querySelectorAll('thead th').length ||
      (rows[0] ? rows[0].children.length : 1);

    if (!noRow) {
      noRow = document.createElement('tr');
      noRow.innerHTML = `<td class="text-center py-4 text-muted fw-semibold" colspan="${colCount}">No results found!</td>`;
      tbody.appendChild(noRow);
    }
    noRow.dataset.noResults = "true";
    noRow.style.display = "none";

    function getDataRows() {
      return Array.from(tbody.querySelectorAll('tr'))
        .filter(tr => tr !== noRow)
        .filter(tr => tr.querySelectorAll('td').length > 1);
    }

    function rowData(tr) {
      return { tr, text: normalize(tr.innerText) };
    }

    let currentPage = 1;

    const searchInputs = Array.from(
      document.querySelectorAll(`input[type="search"][data-search-table="${tableId}"]`)
    );

    const filterSelects = Array.from(
      document.querySelectorAll(`select[data-filter-table="${tableId}"]`)
    );

    function applyFilters(data) {
      const queries = searchInputs.map(inp => normalize(inp.value)).filter(Boolean);

      const activeFilters = filterSelects
        .map(select => ({
          value: select.value,
          selector: select.dataset.filterSelector || '',
          mode: select.dataset.filterMode || 'equals',
        }))
        .filter(f => f.value !== '');

      return data.filter(d => {
        // SEARCH
        if (queries.length) {
          const okSearch = queries.every(q => d.text.includes(q));
          if (!okSearch) return false;
        }

        // FILTERS
        for (const f of activeFilters) {
          const cell = f.selector ? d.tr.querySelector(f.selector) : null;
          const cellText = normalize(cell ? cell.innerText.trim() : '');
          const filterVal = normalize(f.value);

          if (f.mode === 'equals') {
            if (cellText !== filterVal) return false;
          } else if (f.mode === 'contains') {
            if (!cellText.includes(filterVal)) return false;
          } else if (f.mode === 'endswith') {
            if (!cellText.endsWith(filterVal)) return false;
          } else {
            if (!cellText.includes(filterVal)) return false;
          }
        }

        return true;
      });
    }

function renderPager(totalPages) {
  if (!pager) return;
  pager.innerHTML = '';

  pager.style.display = '';

  const addItem = (label, page, disabled=false, active=false, isSpan=false) => {
    const li = document.createElement('li');
    li.className = `page-item ${disabled ? 'disabled' : ''} ${active ? 'active' : ''}`;

    if (isSpan) {
      li.innerHTML = `<span class="page-link">${label}</span>`;
      pager.appendChild(li);
      return;
    }

    const a = document.createElement('a');
    a.className = 'page-link';
    a.href = '#';
    a.textContent = label;

    a.addEventListener('click', (e) => {
      e.preventDefault();
      if (disabled) return;
      showPage(page);
    });

    li.appendChild(a);
    pager.appendChild(li);
  };

  addItem('Previous', currentPage - 1, currentPage === 1);

  if (totalPages <= 1) {
    addItem('1', 1, true, true, true); 
  } else {
    const start = Math.max(1, currentPage - 2);
    const end   = Math.min(totalPages, currentPage + 2);

    if (start > 1) addItem('1', 1);
    if (start > 2) addItem('…', 0, true, false, true);

    for (let i = start; i <= end; i++) {
      addItem(String(i), i, false, i === currentPage);
    }

    if (end < totalPages - 1) addItem('…', 0, true, false, true);
    if (end < totalPages) addItem(String(totalPages), totalPages);
  }

  addItem('Next', currentPage + 1, currentPage === totalPages);
}


    function showPage(page) {
      const rows = getDataRows();
      const data = rows.map(rowData);

      const filtered = applyFilters(data);

      data.forEach(d => d.tr.style.display = 'none');

      if (filtered.length === 0) {
        noRow.style.display = '';
        if (pager) pager.style.display = 'none';
        currentPage = 1;
        return;
      }

      noRow.style.display = 'none';

      const totalPages = Math.max(1, Math.ceil(filtered.length / perPage));
      currentPage = Math.min(Math.max(1, page), totalPages);

      const start = (currentPage - 1) * perPage;
      const end   = start + perPage;

      filtered.slice(start, end).forEach(d => d.tr.style.display = '');

      renderPager(totalPages);
    }

    const debounce = (fn, ms=150) => {
      let t;
      return (...args) => {
        clearTimeout(t);
        t = setTimeout(() => fn(...args), ms);
      };
    };

    searchInputs.forEach(inp => inp.addEventListener('input', debounce(() => showPage(1), 150)));
    filterSelects.forEach(sel => sel.addEventListener('change', () => showPage(1)));

    showPage(1);
  });

  // EDIT USER MODAL
  const editUserModal = document.getElementById('editUserModal');
  if (editUserModal) {
    editUserModal.addEventListener('show.bs.modal', (event) => {
      const btn = event.relatedTarget;
      if (!btn) return;

      const tr = btn.closest('tr');
      if (!tr) return;

      const idEl    = document.getElementById('edit_user_id');
      const nameEl  = document.getElementById('edit_name');
      const emailEl = document.getElementById('edit_email');
      const roleEl  = document.getElementById('edit_role');

      if (!idEl || !nameEl || !emailEl || !roleEl) return;

      idEl.value    = btn.getAttribute('data-id') || '';
      nameEl.value  = tr.querySelector('.user-name')?.innerText.trim()  || '';
      emailEl.value = tr.querySelector('.user-email')?.innerText.trim() || '';
      roleEl.value  = tr.querySelector('.user-role')?.innerText.trim()  || '';
    });
  }
});
</script>



<?php include('../includes/footer.php'); ?>

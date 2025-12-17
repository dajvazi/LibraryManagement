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
<div class="page-wrap">
  <div class="container py-3">
    <div class="row">
      <div class="col-md-12">
        <div class="row">
          <div class="col-12 ">
            <?php if(isset($_SESSION['message'])) { ?>
              <div class="alert alert-info alert-dismissible fade show" role="alert">
                <strong>Hey! </strong><?= $_SESSION['message']; ?>.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
              </div>
            <?php unset($_SESSION['message']); } ?>

              <div class="col-md-6">
                <span class="mb-0 fs-2 fw-bold">My Books</span>
                <p class="mb-4 text-muted"><?= getCountNo("books");?> books in your library</p>
              </div>
          </div>

          <div class="col-12 mb-3">
            <div class="bg-white p-3 rounded">
              <div class="row g-3 align-items-center">

                <div class="col-md-6">
                  <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0">
                      <i class="bi bi-search"></i>
                    </span>
                    <input type="search" class="form-control border-start-0" id="mbSearch"
                      placeholder="Search books, genre..." aria-label="Search">
                  </div>
                </div>

                <div class="col-md-3">
                  <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0">
                      <i class="bi bi-funnel"></i>
                    </span>
                    <select class="form-select border-start-0"  id="mbGenre">
                      <option value="" selected disabled>Select Genre</option>
                      <option value="Fantasy">Fantasy</option>
                      <option value="Thriller">Thriller</option>
                      <option value="Horror">Horror</option>
                      <option value="Biography">Biography</option>
                      <option value="Business">Business</option>
                      <option value="Technology">Technology</option>
                      <option value="Other">Other</option>
                    </select>
                  </div>
                </div>

                <div class="col-md-3">
                  <div class="input-group">
                    <span class="input-group-text bg-transparent border-end-0">
                      <i class="bi bi-check2-circle"></i>
                    </span>
                    <select class="form-select border-start-0" id="mbStatus">
                      <option value="" selected disabled>Select status</option>
                      <option>Planned</option>
                      <option>Reading</option>
                      <option>Completed</option>
                    </select>
                  </div>
                </div>

              </div>
            </div>
          </div>

          <div class="col-12 mt-3">
            <div class="card shadow-sm border-1 ">
              <div class="card-body p-0 ">
                <div class="table-responsive rounded">
                  <table class="table table-hover align-middle mb-0" id="myBooksTable">
                    <thead class="table-light">
                      <tr class="text-muted">
                        <th class="ps-3">Title</th>
                        <th>Author</th>
                        <th>Genre</th>
                        <th>Status</th>
                        <th>Added</th>
                        <th class="text-center pe-3">Actions</th>
                      </tr>
                    </thead>

                    <tbody>
                      <?php
                        $books = getAll("books");
                        if($books && mysqli_num_rows($books) > 0){
                          foreach ($books as $book) {
                      ?>
                        <tr>
                          <td class="ps-3 fw-semibold book-title"><?= htmlspecialchars($book['title']) ?></td>
                          <td class="book-author"><?= htmlspecialchars($book['author']) ?></td>
                          <td class="book-genre"><?= htmlspecialchars($book['genre']) ?></td>
                          <td>
                            <span class="badge bg-warning text-dark book-status"><?= htmlspecialchars($book['status']) ?></span>
                          </td>
                          <td><?= htmlspecialchars($book['added_at']) ?></td>
                          <td class="text-center pe-3">
                            <button type="button"
                              class="btn btn-sm btn-outline-primary editBtn"
                              data-bs-toggle="modal"
                              data-bs-target="#editBookModal"
                              data-id="<?= $book['id'] ?>">
                              <i class="bi bi-pencil"></i>
                            </button>

                            <form method="POST" action="../functions/handleBooks.php" class="d-inline"
                              onsubmit="return confirm('Are you sure you want to delete this book?');">
                              <input type="hidden" name="book_id" value="<?= $book['id'] ?>">
                              <button type="submit" name="deleteBookBtn" class="btn btn-danger btn-sm">
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
                          <td colspan="6" class="text-center text-muted fw-semibold">No results found!</td>
                        </tr>
                      <?php } ?>
                    </tbody>

                  </table>
                </div>
              </div>
            </div>

            <nav class="mt-3">
              <ul class="pagination justify-content-center mb-0" id="myBooksPagination"></ul>
            </nav>

          </div>

        </div>
      </div>
    </div>
  </div>
</div>

<!-- Edit Book Modal -->
<div class="modal fade" id="editBookModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <form class="modal-content shadow-sm" method="POST" action="../functions/handleBooks.php">
      <div class="modal-header">
        <h5 class="modal-title fw-semibold"><i class="bi bi-pencil-square me-1"></i> Edit Book</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <input type="hidden" name="book_id" id="edit_book_id">

        <div class="mb-3">
          <label class="form-label">Title</label>
          <input type="text" name="title" id="edit_title" class="form-control" required>
        </div>

        <div class="mb-3">
          <label class="form-label">Author</label>
          <input type="text" name="author" id="edit_author" class="form-control" required>
        </div>

        <div class="mt-3">
          <label class="form-label">Genre</label>
          <select name="genre" id="edit_genre" class="form-select" required>
            <option value="" selected disabled>Select genre</option>
            <option value="Fantasy">Fantasy</option>
            <option value="Thriller">Thriller</option>
            <option value="Horror">Horror</option>
            <option value="Biography">Biography</option>
            <option value="Business">Business</option>
            <option value="Technology">Technology</option>
            <option value="Other">Other</option>
          </select>
        </div>

        <div class="mt-3">
          <label class="form-label">Status</label>
          <select name="status" id="edit_status" class="form-select" required>
            <option value="" selected disabled>Select status</option>
            <option value="Planned">Planned</option>
            <option value="Reading">Reading</option>
            <option value="Completed">Completed</option>
          </select>
        </div>
      </div>

      <div class="modal-footer">
        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" name="updateBookBtn" class="btn btn-success">
          <i class="bi bi-check2 me-1"></i> Save Changes
        </button>
      </div>
    </form>
  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {

  // --------- Helper: badge colors ----------
  function applyStatusBadges(scope = document) {
    scope.querySelectorAll('.book-status').forEach(badge => {
      const t = (badge.innerText || '').trim().toLowerCase();

      badge.classList.remove('bg-warning','text-dark','status-planned','status-reading','status-completed');

      if (t === 'planned') badge.classList.add('badge','status-planned');
      else if (t === 'reading') badge.classList.add('badge','status-reading');
      else if (t === 'completed') badge.classList.add('badge','status-completed');
      else badge.classList.add('badge','status-reading');
    });
  }

  // ---------------- 1) Edit Modal ----------------
  const modalEl = document.getElementById('editBookModal');
  if (modalEl) {
    modalEl.addEventListener('show.bs.modal', (event) => {
      const btn = event.relatedTarget;
      if (!btn) return;

      const tr = btn.closest('tr');
      if (!tr) return;

      const idEl     = document.getElementById('edit_book_id');
      const titleEl  = document.getElementById('edit_title');
      const authorEl = document.getElementById('edit_author');
      const genreEl  = document.getElementById('edit_genre');
      const statusEl = document.getElementById('edit_status');
      if (!idEl || !titleEl || !authorEl || !genreEl || !statusEl) return;

      idEl.value     = btn.getAttribute('data-id') || '';
      titleEl.value  = tr.querySelector('.book-title')?.innerText.trim()  || '';
      authorEl.value = tr.querySelector('.book-author')?.innerText.trim() || '';
      genreEl.value  = tr.querySelector('.book-genre')?.innerText.trim()  || '';

      const statusText = (tr.querySelector('.book-status')?.innerText || '').trim().toLowerCase();
      statusEl.value =
        statusText === 'completed' ? 'Completed' :
        statusText === 'planned'   ? 'Planned'   : 'Reading';
    });
  }

  const table = document.getElementById('myBooksTable');
  const tbody = table?.querySelector('tbody');
  const pager = document.getElementById('myBooksPagination');

  const searchEl = document.getElementById('mbSearch');
  const genreSel = document.getElementById('mbGenre');
  const statusSel= document.getElementById('mbStatus');

  if (!table || !tbody || !pager) return;

  const perPage = 5;
  let currentPage = 1;

  const normalize = (s) => (s || '').toLowerCase();

  let noRow = Array.from(tbody.querySelectorAll('tr')).find(tr => {
    const td = tr.querySelector('td[colspan]');
    return td && normalize(td.innerText).includes('no results');
  }) || null;

  if (!noRow) {
    const colCount =
      table.querySelectorAll('thead th').length ||
      (tbody.querySelector('tr') ? tbody.querySelector('tr').children.length : 1);

    noRow = document.createElement('tr');
    noRow.innerHTML = `<td class="text-center text-muted fw-semibold py-4" colspan="${colCount}">No results found!</td>`;
    tbody.appendChild(noRow);
  }
  noRow.dataset.noResults = "true";
  noRow.style.display = "none";

  function ensureAllOption(select, label) {
    if (!select) return;
    if (select.options[0] && select.options[0].disabled) {
      select.options[0].disabled = false;
      select.options[0].selected = true;
      select.options[0].value = '';
      select.options[0].textContent = label;
    }
  }
  ensureAllOption(genreSel, 'All genres');
  ensureAllOption(statusSel, 'All statuses');

  function getDataRows() {
    return Array.from(tbody.querySelectorAll('tr'))
      .filter(tr => tr !== noRow)
      .filter(tr => tr.querySelectorAll('td').length > 1);
  }

  function rowData(tr) {
    const tds = tr.querySelectorAll('td');
    const title  = tr.querySelector('.book-title')?.innerText.trim()  || '';
    const author = tr.querySelector('.book-author')?.innerText.trim() || '';
    const genre  = tr.querySelector('.book-genre')?.innerText.trim()  || '';
    const status = tr.querySelector('.book-status')?.innerText.trim() || '';
    const added  = tds[4]?.innerText.trim() || '';
    return { tr, title, author, genre, status, added };
  }

  function applyFilters(data) {
    const q  = normalize(searchEl?.value || '');
    const g  = genreSel?.value || '';
    const st = statusSel?.value || '';

    return data.filter(d => {
      const matchesSearch = !q || (
        normalize(d.title).includes(q) ||
        normalize(d.author).includes(q) ||
        normalize(d.genre).includes(q) ||
        normalize(d.status).includes(q) ||
        normalize(d.added).includes(q)
      );
      const matchesGenre  = !g  || d.genre === g;
      const matchesStatus = !st || d.status === st;
      return matchesSearch && matchesGenre && matchesStatus;
    });
  }

function renderPager(totalPages) {
  pager.innerHTML = '';
  pager.style.display = '';

  const addItem = (label, page, disabled = false, active = false) => {
    const li = document.createElement('li');
    li.className = `page-item ${disabled ? 'disabled' : ''} ${active ? 'active' : ''}`;

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

  const start = Math.max(1, currentPage - 2);
  const end   = Math.min(totalPages, currentPage + 2);

  if (start > 1) addItem('1', 1);

  if (start > 2) {
    const li = document.createElement('li');
    li.className = 'page-item disabled';
    li.innerHTML = '<span class="page-link">…</span>';
    pager.appendChild(li);
  }

  for (let i = start; i <= end; i++) {
    addItem(String(i), i, false, i === currentPage);
  }

  if (end < totalPages - 1) {
    const li = document.createElement('li');
    li.className = 'page-item disabled';
    li.innerHTML = '<span class="page-link">…</span>';
    pager.appendChild(li);
  }

  if (end < totalPages) addItem(String(totalPages), totalPages);

  addItem('Next', currentPage + 1, currentPage === totalPages);
}


  function showPage(page) {
    const rows = getDataRows();
    const data = rows.map(rowData);
    const filtered = applyFilters(data);

    data.forEach(d => d.tr.style.display = 'none');

    if (filtered.length === 0) {
      noRow.style.display = '';
      pager.style.display = 'none'; 
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
    let t; return (...args) => { clearTimeout(t); t = setTimeout(() => fn(...args), ms); };
  };

  if (searchEl) searchEl.addEventListener('input', debounce(() => showPage(1), 150));
  if (genreSel) genreSel.addEventListener('change', () => showPage(1));
  if (statusSel) statusSel.addEventListener('change', () => showPage(1));

  applyStatusBadges(document);
  showPage(1);
});
</script>

<?php include('../includes/footer.php'); ?>

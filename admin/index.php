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
      <strong>Hey!</strong> <?= $_SESSION['message']; ?>
      <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
  <?php unset($_SESSION['message']); } ?>

  <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3 mb-4">
    <div>
      <h2 class="fw-bold mb-1">Dashboard</h2>
      <p class="text-muted mb-0">Check the users, books and activity at a glance.</p>
    </div>
  </div>

  <div class="row g-3 mb-4">

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card shadow-sm border-0 rounded-4 h-100">
        <div class="card-body p-4 d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">Total Books</div>
            <div class="h4 fw-bold mb-0"><?= getCountNo("books")?></div>
          </div>
          <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center" style="width:46px; height:46px;">
            <i class="bi bi-book fs-4"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card shadow-sm border-0 rounded-4 h-100">
        <div class="card-body p-4 d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">Books added today</div>
            <div class="h4 fw-bold mb-0"><?= getTodaysBooksCount()?></div>
          </div>
          <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center" style="width:46px; height:46px;">
            <i class="bi bi-bar-chart-line fs-4"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card shadow-sm border-0 rounded-4 h-100">
        <div class="card-body p-4 d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">Reading Books</div>
            <div class="h4 fw-bold mb-0"><?= getReadingBooksCount()?></div>
          </div>
          <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center" style="width:46px; height:46px;">
            <i class="bi bi-journal fs-4"></i>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-sm-6 col-xl-3">
      <div class="card shadow-sm border-0 rounded-4 h-100">
        <div class="card-body p-4 d-flex justify-content-between align-items-center">
          <div>
            <div class="text-muted small">Completed Books</div>
            <div class="h4 fw-bold mb-0"><?= getCompletedBooksCount() ?></div>
          </div>
          <div class="bg-dark text-white rounded-circle d-flex align-items-center justify-content-center" style="width:46px; height:46px;">
            <i class="bi bi-check2 fs-4"></i>
          </div>
        </div>
      </div>
    </div>

  </div>

  <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2 mb-2">
    <div>
      <div class="fw-bold">Most Recent Books Added</div>
      <div class="text-muted small">Latest entries in library</div>
    </div>
  </div>

  <div class="card shadow-sm border-0 rounded-4">
    <div class="card-body p-0">
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="recentBooksTable">
          <thead class="table-light">
            <tr class="text-muted text-center">
              <th class="text-start ps-4">Title</th>
              <th>Author</th>
              <th>Genre</th>
              <th>Status</th>
              <th>Added</th>
            </tr>
          </thead>
          <tbody>
            <?php
              $books = getRecentBooks();
              if ($books && mysqli_num_rows($books) > 0) {
                foreach ($books as $book) {
            ?>
              <tr class="text-center">
                <td class="text-start ps-4 fw-semibold">
                  <?= htmlspecialchars($book['title']) ?>
                </td>
                <td><?= htmlspecialchars($book['author']) ?></td>
                <td><?= htmlspecialchars($book['genre']) ?></td>
                <td>
                  <span class="badge book-status"><?= htmlspecialchars($book['status']) ?></span>
                </td>
                <td class="text-muted"><?= htmlspecialchars($book['added_at']) ?></td>
              </tr>
            <?php
                }
              } else {
            ?>
              <tr>
                <td colspan="5" class="text-center text-muted fw-semibold py-4">No results found!</td>
              </tr>
            <?php } ?>
          </tbody>
        </table>
      </div>
    </div>
  </div>

  <nav class="mt-3">
    <ul class="pagination justify-content-center mb-0" id="recentBooksPagination"></ul>
  </nav>

</div>


<script>
document.addEventListener('DOMContentLoaded', () => {
  const table = document.getElementById('recentBooksTable');
  const tbody = table?.querySelector('tbody');
  const pager = document.getElementById('recentBooksPagination');

  if (!table || !tbody || !pager) return;

  const perPage = 5;
  let currentPage = 1;

  const normalize = (s) => (s || '').trim().toLowerCase();

  function applyStatusBadges(scope = document) {
    scope.querySelectorAll('.book-status').forEach(badge => {
      const t = normalize(badge.innerText);

      badge.classList.remove(
        'text-bg-warning','bg-warning','text-dark',
        'status-planned','status-reading','status-completed'
      );

      if (t === 'planned') badge.classList.add('status-planned');
      else if (t === 'reading') badge.classList.add('status-reading');
      else if (t === 'completed') badge.classList.add('status-completed');
      else badge.classList.add('status-reading');
    });
  }

  function collectRows() {
    return Array.from(tbody.querySelectorAll('tr'))
      .filter(tr => tr.querySelectorAll('td').length > 1);
  }

  function renderPager(totalPages) {
    pager.innerHTML = '';

    if (totalPages <= 1) {
      pager.style.display = 'none';
      return;
    }
    pager.style.display = '';

    const addItem = (label, page, disabled=false, active=false) => {
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
    const rows = collectRows();

    if (rows.length === 0) {
      pager.innerHTML = '';
      pager.style.display = 'none';
      return;
    }

    const totalPages = Math.max(1, Math.ceil(rows.length / perPage));
    currentPage = Math.min(Math.max(1, page), totalPages);

    const start = (currentPage - 1) * perPage;
    const end   = start + perPage;

    rows.forEach((tr, idx) => {
      tr.style.display = (idx >= start && idx < end) ? '' : 'none';
    });

    renderPager(totalPages);
  }

  applyStatusBadges(document);
  showPage(1);
});
</script>


<?php include('../includes/footer.php'); ?>

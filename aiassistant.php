<?php
include('includes/header.php');
include('functions/functions.php');

if (!isset($_SESSION['auth'])) {
  header('Location: login.php');
  exit;
}

$BASE_URL = "/LibraryManagement"; 
?>

<div class="container-fluid py-3" style="height:calc(100vh - 80px);">
  <div class="row g-4 align-items-stretch h-100">

    <div class="col-12 col-lg-8 h-100">
      <div class="card border-0 shadow-sm rounded-4 h-100 d-flex flex-column">

        <div class="card-header bg-white border-0 rounded-top-4 py-3">
          <div class="d-flex align-items-center gap-3">
            <div class="rounded-4 d-flex align-items-center justify-content-center"
                 style="width:44px;height:44px;background:rgba(25, 95, 135, 0.12);">
              <i class="bi bi-stars text-success fs-4"></i>
            </div>

            <div class="flex-grow-1">
              <div class="fw-bold fs-5 lh-1">AI Assistant</div>
              <span class="badge text-bg-primary mt-2">User mode: Your library only</span>
            </div>
          </div>
        </div>

        <div class="card-body d-flex flex-column gap-3 flex-grow-1"
             id="chatBody"
             style="overflow-y:auto; min-height:0;">
          <div class="d-flex gap-3 align-items-start">
            <div class="rounded-4 d-flex align-items-center justify-content-center flex-shrink-0"
                 style="width:40px;height:40px;background:rgba(25, 107, 135, 0.12);">
              <i class="bi bi-stars text-success"></i>
            </div>

            <div class="px-3 py-3 rounded-4 bg-body-tertiary border" style="max-width: 520px;">
              Hello! Ask me about your library (completed, reading, genres, totals).
            </div>
          </div>
        </div>

        <div class="card-footer bg-white border-0 rounded-bottom-4">
          <div class="small text-muted mt-2" id="typingHint" style="display:none;">
            Assistant is typing…
          </div>
          <form class="d-flex gap-2 align-items-center" id="chatForm">
            <input type="text"
                   class="form-control form-control-lg rounded-pill"
                   id="chatInput"
                   placeholder="Ask about your library..."
                   autocomplete="off"
                   required>
            <button type="submit"
                    class="btn btn-success rounded-circle d-flex align-items-center justify-content-center"
                    style="width:52px;height:52px;">
              <i class="bi bi-send-fill"></i>
            </button>
          </form>
        </div>

      </div>
    </div>

    <div class="col-12 col-lg-4 h-100">
      <div class="card border-0 shadow-sm rounded-4 h-100">
        <div class="card-body p-4 p-lg-5">
          <div class="fw-bold fs-4 mb-4">How to use</div>

          <div class="d-flex gap-3 mb-3">
            <div class="text-success fs-5"><i class="bi bi-book"></i></div>
            <div>Ask about reading status, completed books, wishlist</div>
          </div>

          <div class="d-flex gap-3 mb-3">
            <div class="text-success fs-5"><i class="bi bi-book"></i></div>
            <div>Query authors, genres, and totals</div>
          </div>

          <div class="d-flex gap-3 mb-4">
            <div class="text-success fs-5"><i class="bi bi-bar-chart"></i></div>
            <div>Get statistics about your collection</div>
          </div>

          <div class="rounded-4 p-3 border border-success-subtle bg-success-subtle">
            <div class="d-flex gap-2 align-items-start">
              <i class="bi bi-lock-fill text-success mt-1"></i>
              <div class="text-success-emphasis">
                <div class="fw-semibold">User mode</div>
                <div>Assistant only uses your data.</div>
              </div>
            </div>
          </div>

        </div>
      </div>
    </div>

  </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
  const chatBody   = document.getElementById('chatBody');
  const chatForm   = document.getElementById('chatForm');
  const chatInput  = document.getElementById('chatInput');
  const typingHint = document.getElementById('typingHint');

  const API_URL = "<?= $BASE_URL ?>/api/chat_reply.php";

  function escapeHtml(str){
    return String(str)
      .replaceAll('&','&amp;')
      .replaceAll('<','&lt;')
      .replaceAll('>','&gt;')
      .replaceAll('"','&quot;')
      .replaceAll("'","&#039;");
  }

  function scrollBottom(){
    chatBody.scrollTop = chatBody.scrollHeight;
  }

  function addUserMsg(text){
    const wrap = document.createElement('div');
    wrap.className = "d-flex justify-content-end";
    wrap.innerHTML = `
      <div class="px-3 py-3 rounded-4 bg-success text-white" style="max-width:520px;">
        ${escapeHtml(text)}
      </div>
    `;
    chatBody.appendChild(wrap);
    scrollBottom();
  }

  function addBotMsg(text){
    const wrap = document.createElement('div');
    wrap.className = "d-flex gap-3 align-items-start";
    wrap.innerHTML = `
      <div class="rounded-4 d-flex align-items-center justify-content-center flex-shrink-0"
           style="width:40px;height:40px;background:rgba(25, 93, 135, 0.12);">
        <i class="bi bi-stars text-success"></i>
      </div>
      <div class="px-3 py-3 rounded-4 bg-body-tertiary border" style="max-width:520px;">
        ${escapeHtml(text)}
      </div>
    `;
    chatBody.appendChild(wrap);
    scrollBottom();
  }

  async function askAssistant(message){
    typingHint.style.display = "block";

    const res = await fetch(API_URL, {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      credentials: 'same-origin',
      body: JSON.stringify({ message })
    });

    typingHint.style.display = "none";

    const contentType = res.headers.get('content-type') || '';
    const rawText = await res.text();

    if(!res.ok){
      addBotMsg("Server error: " + rawText);
      return;
    }

    if(!contentType.includes('application/json')){
      addBotMsg("Server didn't return JSON:\n" + rawText);
      return;
    }

    let data;
    try { data = JSON.parse(rawText); }
    catch(e){
      addBotMsg("Invalid JSON:\n" + rawText);
      return;
    }

    if (data.error) addBotMsg("Error: " + data.error);
    else addBotMsg(data.answer ?? "No reply.");
  }

  chatForm.addEventListener('submit', async (e) => {
    e.preventDefault();

    const msg = chatInput.value.trim();
    if(!msg) return;

    addUserMsg(msg);
    chatInput.value = "";
    chatInput.focus();

    try {
      await askAssistant(msg);
    } catch(err) {
      typingHint.style.display = "none";
      addBotMsg("Network/JS error: " + err.message);
    }
  });
});
</script>

<?php include('includes/footer.php'); ?>

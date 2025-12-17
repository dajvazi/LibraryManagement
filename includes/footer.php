    </div>
  </main>

  <!-- ============= SCRIPTS ============= -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <script>
    <?php if(isset($_SESSION['message'])) {?>
      <?= $_SESSION['message'] ?>
    <?php 
      unset($_SESSION['message']);
    }
    ?>
  </script>

  
</body>
</html>
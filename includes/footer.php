
    </div><!-- /.page-content -->

    <footer class="main-footer">
      &copy; <?= date('Y') ?> HostelCare &mdash; Hostel Complaint Tracking System
    </footer>

  </div><!-- /.main-content -->
</div><!-- /.app-shell -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
const sidebar   = document.getElementById('sidebar');
const overlay   = document.getElementById('sidebarOverlay');
const toggleBtn = document.getElementById('sidebarToggle');
if (toggleBtn) {
  toggleBtn.addEventListener('click', () => {
    const open = sidebar.classList.toggle('open');
    overlay.style.display = open ? 'block' : 'none';
  });
}
if (overlay) {
  overlay.addEventListener('click', () => {
    sidebar.classList.remove('open');
    overlay.style.display = 'none';
  });
}
document.querySelectorAll('.flash-zone .alert').forEach(el => {
  setTimeout(() => { el.style.opacity='0'; el.style.transition='opacity .4s'; setTimeout(()=>el.remove(),400); }, 5000);
});
</script>
</body>
</html>

        </main>
    </div>
</div>
<script>
function toggleBusinessSidebar() {
    document.getElementById('businessSidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('active');
}
document.querySelectorAll('#businessSidebar .sidebar-nav-link').forEach(function (link) {
    link.addEventListener('click', function () {
        document.querySelectorAll('#businessSidebar .sidebar-nav-link').forEach(function (item) {
            item.classList.remove('active');
        });
        link.classList.add('active');
        document.getElementById('businessSidebar').classList.remove('open');
        document.getElementById('sidebarOverlay').classList.remove('active');
    });
});
</script>
</body>
</html>

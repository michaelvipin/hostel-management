<?php
// includes/ui/header.php — header that toggles sidebar and preserves layout
?>
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

<header class="bg-white border-b z-20">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center h-16">
      <div class="flex items-center gap-3">
        <button id="sidebarToggle" aria-label="Toggle sidebar" class="md:hidden p-2 rounded hover:bg-gray-100">
          <!-- hamburger -->
          <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-gray-700" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
        </button>
        <a href="<?= e(url('public/login.php')) ?>" class="text-xl font-semibold text-indigo-600">HostelMgmt</a>
      </div>

      <div class="flex items-center space-x-4">
        <div class="text-sm text-gray-700 hidden sm:block">Hello, <strong><?= e($_SESSION['user']['name'] ?? 'User') ?></strong></div>
        <a href="<?= e(url('public/change_password.php')) ?>" class="text-sm text-indigo-600 hidden sm:inline">Change password</a>
        <a href="<?= e(url('public/logout.php')) ?>" class="px-3 py-2 rounded text-sm bg-red-50 text-red-700">Logout</a>
      </div>
    </div>
  </div>
</header>

<script>
document.addEventListener('DOMContentLoaded', function(){
  const btn = document.getElementById('sidebarToggle');
  btn?.addEventListener('click', function(){
    const sb = document.getElementById('sidebar');
    if (!sb) return;
    // toggle mobile sidebar
    sb.classList.toggle('-translate-x-full');
  });
});
</script>

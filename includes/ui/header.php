<?php
// includes/ui/header.php
?>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
<link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">

<style>
    body { font-family: 'Inter', sans-serif; }
</style>

<header class="bg-white/90 backdrop-blur-md border-b border-gray-200 sticky top-0 z-20">
  <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
    <div class="flex justify-between items-center h-16">
      
      <div class="flex items-center gap-4">
        <button id="sidebarToggle" aria-label="Toggle sidebar" class="md:hidden p-2 -ml-2 rounded-md text-gray-600 hover:text-indigo-600 hover:bg-indigo-50 transition-colors">
          <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
          </svg>
        </button>

        <a href="<?= e(url('public/login.php')) ?>" class="group flex items-center gap-2">
          <div class="bg-indigo-600 text-white p-1.5 rounded-lg shadow-sm group-hover:bg-indigo-700 transition-colors">
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
               <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
            </svg>
          </div>
          <span class="text-lg font-bold text-gray-900 tracking-tight group-hover:text-indigo-600 transition-colors">
            Hostel<span class="text-indigo-600">Mgmt</span>
          </span>
        </a>
      </div>

      <div class="flex items-center space-x-3 sm:space-x-6">
        
        <div class="hidden sm:flex flex-col items-end mr-2">
            <span class="text-xs text-gray-500 uppercase font-semibold tracking-wider">Signed in as</span>
            <span class="text-sm font-medium text-gray-900"><?= e($_SESSION['user']['name'] ?? 'Guest User') ?></span>
        </div>

        <div class="hidden sm:block h-8 w-px bg-gray-200"></div>

        <div class="flex items-center gap-2">
            <a href="<?= e(url('public/change_password.php')) ?>" class="p-2 text-gray-500 hover:text-indigo-600 hover:bg-gray-100 rounded-full transition-all" title="Change Password">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11.536 11 13 12.464l-1.414 1.414L8.536 12 7 13.536 5.464 12l-1.414 1.414a2.5 2.5 0 11-3.536-3.536l1.414-1.414L3.464 7 5 5.464 6.464 7 8 5.464 9.464 7 11 5.464 12.536 7 14 5.464 15.464 7z" />
                </svg>
            </a>

            <a href="<?= e(url('public/logout.php')) ?>" class="flex items-center gap-2 px-4 py-2 rounded-lg text-sm font-medium bg-white text-red-600 border border-red-100 hover:bg-red-50 hover:border-red-200 shadow-sm transition-all group">
                <span>Logout</span>
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 group-hover:translate-x-0.5 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                </svg>
            </a>
        </div>

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
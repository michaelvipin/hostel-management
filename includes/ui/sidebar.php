<?php
// includes/ui/sidebar.php  — fixed to avoid overlapping main content
$role = $_SESSION['user']['role'] ?? 'student';
?>
<!-- Sidebar -->
<nav id="sidebar" class="fixed inset-y-0 left-0 z-30 w-64 bg-white border-r transform -translate-x-full md:translate-x-0 transition-transform duration-200 ease-in-out">
  <div class="h-full flex flex-col">
    <div class="px-6 py-4 border-b">
      <div class="text-lg font-semibold text-indigo-600">HostelMgmt</div>
      <div class="text-sm text-gray-500 mt-1"><?= e($_SESSION['user']['email'] ?? '') ?></div>
    </div>

    <div class="p-4 flex-1 overflow-y-auto">
      <ul class="space-y-1 text-sm">
        <?php if ($role === 'admin'): ?>
          <li><a href="<?= e(url('admin/dashboard.php')) ?>" class="block px-3 py-2 rounded hover:bg-indigo-50">Dashboard</a></li>
          <li><a href="<?= e(url('admin/leaves_dashboard.php')) ?>" class="block px-3 py-2 rounded hover:bg-indigo-50">Leaves</a></li>
          <li><a href="<?= e(url('admin/manage_students.php')) ?>" class="block px-3 py-2 rounded hover:bg-indigo-50">Manage Students</a></li>
        <?php elseif ($role === 'warden'): ?>
          <li><a href="<?= e(url('warden/dashboard.php')) ?>" class="block px-3 py-2 rounded hover:bg-indigo-50">Dashboard</a></li>
          <li><a href="<?= e(url('warden/students.php')) ?>" class="block px-3 py-2 rounded hover:bg-indigo-50">Students</a></li>
          <li><a href="<?= e(url('warden/mark_attendance.php')) ?>" class="block px-3 py-2 rounded hover:bg-indigo-50">Mark Attendance</a></li>
          <li><a href="<?= e(url('warden/approve_leave.php')) ?>" class="block px-3 py-2 rounded hover:bg-indigo-50">Approve Leaves</a></li>
        <?php else: ?>
          <li><a href="<?= e(url('student/dashboard.php')) ?>" class="block px-3 py-2 rounded hover:bg-indigo-50">Dashboard</a></li>
          <li><a href="<?= e(url('student/apply_leave.php')) ?>" class="block px-3 py-2 rounded hover:bg-indigo-50">Apply Leave</a></li>
          <li><a href="<?= e(url('student/view_attendance.php')) ?>" class="block px-3 py-2 rounded hover:bg-indigo-50">My Attendance</a></li>
        <?php endif; ?>
      </ul>
    </div>

    <div class="px-4 py-4 border-t text-sm text-gray-500">
      &copy; <?= date('Y') ?> HostelMgmt
    </div>
  </div>
</nav>

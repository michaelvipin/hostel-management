SELECT
  SUM(status='present') AS present_count,
  COUNT(*) AS total_marked
FROM attendance
WHERE student_id = :student_id;
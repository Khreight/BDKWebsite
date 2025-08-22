SELECT status, COUNT(*)
FROM registration
WHERE race = 1
GROUP BY status;

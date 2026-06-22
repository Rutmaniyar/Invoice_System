ALTER TABLE invoices ADD COLUMN paid_at TIMESTAMP NULL DEFAULT NULL AFTER viewed_at;

-- Only auto-merges groups that share a normalized name AND a non-empty matching email.
-- Two unrelated clients with the same name but no email on file are left alone (too risky to guess).
CREATE TEMPORARY TABLE client_dupe_groups AS
SELECT
  MIN(id) AS canonical_id,
  LOWER(TRIM(name)) AS norm_name,
  LOWER(TRIM(email)) AS norm_email
FROM clients
WHERE deleted_at IS NULL AND TRIM(name) <> '' AND TRIM(COALESCE(email, '')) <> ''
GROUP BY LOWER(TRIM(name)), LOWER(TRIM(email))
HAVING COUNT(*) > 1;

CREATE TEMPORARY TABLE client_dupe_map AS
SELECT c.id AS dupe_id, g.canonical_id AS canonical_id
FROM clients c
INNER JOIN client_dupe_groups g
  ON LOWER(TRIM(c.name)) = g.norm_name
 AND LOWER(TRIM(COALESCE(c.email, ''))) = g.norm_email
WHERE c.deleted_at IS NULL AND c.id <> g.canonical_id;

UPDATE invoices i
INNER JOIN client_dupe_map m ON m.dupe_id = i.client_id
SET i.client_id = m.canonical_id;

UPDATE quotes q
INNER JOIN client_dupe_map m ON m.dupe_id = q.client_id
SET q.client_id = m.canonical_id;

UPDATE recurring_invoices r
INNER JOIN client_dupe_map m ON m.dupe_id = r.client_id
SET r.client_id = m.canonical_id;

UPDATE clients c
INNER JOIN client_dupe_map m ON m.dupe_id = c.id
SET c.deleted_at = NOW(), c.updated_at = NOW();

DROP TEMPORARY TABLE IF EXISTS client_dupe_map;
DROP TEMPORARY TABLE IF EXISTS client_dupe_groups;

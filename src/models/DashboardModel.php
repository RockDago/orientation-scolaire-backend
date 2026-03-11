<?php


require_once __DIR__ . '/../core/Database.php';

class DashboardModel
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::getConnection();
    }

    public function recordPageView(string $page, ?int $metierId, ?string $ip, ?string $ua, ?string $visitorId = null, ?array $clientInfo = null): void
    {
        try {
          
            if ($ip) {
                $stmtCheck = $this->pdo->prepare(
                    "SELECT COUNT(*) FROM page_views
                     WHERE ip_address = :ip
                       AND viewed_at >= :since"
                );
                $stmtCheck->execute([
                    ':ip'    => $ip,
                    ':since' => date('Y-m-d H:i:s', strtotime('-24 hours')),
                ]);
                if ((int) $stmtCheck->fetchColumn() > 0) {
                    error_log("Visiteur déjà enregistré aujourd'hui (IP: $ip) — ignoré.");
                    return;
                }
            } elseif ($visitorId) {
            
                $stmtCheck = $this->pdo->prepare(
                    "SELECT COUNT(*) FROM page_views
                     WHERE visitor_id = :vid
                       AND viewed_at >= :since"
                );
                $stmtCheck->execute([
                    ':vid'   => $visitorId,
                    ':since' => date('Y-m-d H:i:s', strtotime('-24 hours')),
                ]);
                if ((int) $stmtCheck->fetchColumn() > 0) {
                    error_log("Visiteur déjà enregistré aujourd'hui (visitor_id: $visitorId) — ignoré.");
                    return;
                }
            }
        
            $clientInfoJson = null;
            if ($clientInfo && is_array($clientInfo)) {
                $clientInfoJson = json_encode($clientInfo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                if ($clientInfoJson === false) $clientInfoJson = null;
            }

            $columns      = ['page', 'metier_id', 'ip_address', 'user_agent', 'viewed_at'];
            $placeholders = [':page', ':metier_id', ':ip', ':ua', 'NOW()'];
            $params       = [
                ':page'      => $page,
                ':metier_id' => $metierId,
                ':ip'        => $ip,
                ':ua'        => $ua,
            ];

            if ($this->columnExists('page_views', 'visitor_id')) {
                $columns[]              = 'visitor_id';
                $placeholders[]         = ':visitor_id';
                $params[':visitor_id']  = $visitorId;
            }

            if ($this->columnExists('page_views', 'client_info')) {
                $columns[]             = 'client_info';
                $placeholders[]        = ':client_info';
                $params[':client_info'] = $clientInfoJson;
            }

            $sql  = "INSERT INTO page_views (" . implode(', ', $columns) . ")
                     VALUES ("               . implode(', ', $placeholders) . ")";
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);

            error_log("Nouveau visiteur enregistré — IP: $ip | page: $page");

        } catch (PDOException $e) {
            error_log("Erreur PDO dans recordPageView: " . $e->getMessage());
        }
    }

    public function recordMetierSearch(int $metierId, string $metierLabel, ?string $visitorId = null, ?array $clientInfo = null): void
    {
        try {
            $clientInfoJson = null;
            if ($clientInfo && is_array($clientInfo)) {
                $clientInfoJson = json_encode($clientInfo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            
            $columns = ['metier_id', 'metier_label', 'searched_at'];
            $placeholders = [':id', ':label', 'NOW()'];
            $params = [
                ':id' => $metierId,
                ':label' => $metierLabel
            ];

            if ($this->columnExists('metier_searches', 'visitor_id')) {
                $columns[] = 'visitor_id';
                $placeholders[] = ':visitor_id';
                $params[':visitor_id'] = $visitorId;
            }

            if ($this->columnExists('metier_searches', 'client_info')) {
                $columns[] = 'client_info';
                $placeholders[] = ':client_info';
                $params[':client_info'] = $clientInfoJson;
            }

            $sql = "INSERT INTO metier_searches (" . implode(', ', $columns) . ") 
                    VALUES (" . implode(', ', $placeholders) . ")";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
        } catch (PDOException $e) {
            error_log("Erreur PDO dans recordMetierSearch: " . $e->getMessage());
        }
    }

    public function recordEtablissementSelection(string $metierLabel, string $region, ?string $etablissementNom = null, ?string $visitorId = null, ?array $clientInfo = null): void
    {
        try {
            $clientInfoJson = null;
            if ($clientInfo && is_array($clientInfo)) {
                $clientInfoJson = json_encode($clientInfo, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            }
            
        
            $this->pdo->exec("
                CREATE TABLE IF NOT EXISTS etablissement_selections (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    metier_label VARCHAR(200) NOT NULL,
                    region VARCHAR(100),
                    etablissement_nom VARCHAR(200),
                    visitor_id VARCHAR(50),
                    client_info JSON,
                    selected_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_metier (metier_label),
                    INDEX idx_region (region),
                    INDEX idx_selected_at (selected_at),
                    INDEX idx_visitor_id (visitor_id)
                )
            ");
            
            $columns = ['metier_label', 'region', 'selected_at'];
            $placeholders = [':metier', ':region', 'NOW()'];
            $params = [
                ':metier' => $metierLabel,
                ':region' => $region
            ];

            if ($etablissementNom) {
                $columns[] = 'etablissement_nom';
                $placeholders[] = ':etab';
                $params[':etab'] = $etablissementNom;
            }

            if ($visitorId) {
                $columns[] = 'visitor_id';
                $placeholders[] = ':visitor_id';
                $params[':visitor_id'] = $visitorId;
            }

            if ($clientInfoJson) {
                $columns[] = 'client_info';
                $placeholders[] = ':client_info';
                $params[':client_info'] = $clientInfoJson;
            }

            $sql = "INSERT INTO etablissement_selections (" . implode(', ', $columns) . ") 
                    VALUES (" . implode(', ', $placeholders) . ")";
            
            $stmt = $this->pdo->prepare($sql);
            $stmt->execute($params);
            
        } catch (PDOException $e) {
            error_log("Erreur PDO dans recordEtablissementSelection: " . $e->getMessage());
        }
    }

  
    private function columnExists(string $table, string $column): bool
    {
        try {
            $stmt = $this->pdo->prepare("SHOW COLUMNS FROM $table LIKE :column");
            $stmt->execute([':column' => $column]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            return false;
        }
    }



    public function getTotalViews(string $start, string $end): int
    {
        try {
         
            $query = "SELECT COUNT(*) FROM (
                          SELECT COALESCE(NULLIF(ip_address, ''), visitor_id) AS uid
                          FROM page_views
                          WHERE viewed_at BETWEEN :start AND :end
                          GROUP BY uid
                      ) AS uniq";
            $stmt = $this->pdo->prepare($query);
            $stmt->execute([':start' => $start, ':end' => $end]);
            return (int) $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Erreur getTotalViews: " . $e->getMessage());
            return 0;
        }
    }
    public function getTrendViews(): float
    {
        try {
            $current = $this->pdo->query("
                SELECT COUNT(*) FROM (
                    SELECT COALESCE(NULLIF(ip_address,''), visitor_id) AS uid
                    FROM page_views
                    WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                    GROUP BY uid
                ) AS u
            ")->fetchColumn();

            $previous = $this->pdo->query("
                SELECT COUNT(*) FROM page_views
                WHERE viewed_at BETWEEN
                    DATE_SUB(NOW(), INTERVAL 14 DAY)
                    AND DATE_SUB(NOW(), INTERVAL 7 DAY)
            ")->fetchColumn();

            $current = (int) $current;
            $previous = (int) $previous;

            if ($previous === 0) return 0.0;
            
            return round(($current - $previous) / $previous * 100, 1);
        } catch (PDOException $e) {
            error_log("Erreur getTrendViews: " . $e->getMessage());
            return 0.0;
        }
    }


    public function getMonthlyVisibilite(): array
    {
        try {
        
            $months = [];
            for ($i = 6; $i >= 0; $i--) {
                $date = date('Y-m', strtotime("-$i months"));
                $monthAbbr = date('M', strtotime("-$i months"));
                $monthMap = [
                    'Jan' => 'Janvier', 'Feb' => 'Février', 'Mar' => 'Mars',
                    'Apr' => 'Avril', 'May' => 'Mai', 'Jun' => 'Juin',
                    'Jul' => 'Juillet', 'Aug' => 'Août', 'Sep' => 'Septembre',
                    'Oct' => 'Octobre', 'Nov' => 'Novembre', 'Dec' => 'Décembre',
                ];
                $months[$date] = [
                    'month' => $monthMap[$monthAbbr] ?? $monthAbbr,
                    'visites' => 0,
                ];
            }

            $stmt = $this->pdo->query("
                SELECT
                    period,
                    COUNT(*) AS visites
                FROM (
                    SELECT
                        DATE_FORMAT(viewed_at, '%Y-%m') AS period,
                        COALESCE(NULLIF(ip_address,''), visitor_id) AS uid
                    FROM page_views
                    WHERE viewed_at >= DATE_SUB(CURDATE(), INTERVAL 7 MONTH)
                    GROUP BY period, uid
                ) AS uniq
                GROUP BY period
                ORDER BY period ASC
            ");
            $data = $stmt->fetchAll(PDO::FETCH_ASSOC);


            foreach ($data as $row) {
                if (isset($months[$row['period']])) {
                    $months[$row['period']]['visites'] = (int) $row['visites'];
                }
            }

            return array_values($months);
        } catch (PDOException $e) {
            error_log("Erreur getMonthlyVisibilite: " . $e->getMessage());
            return [];
        }
    }

    public function getWeeklyActivity(): array
    {
        try {
            $stmt = $this->pdo->query("
                SELECT
                    DAYNAME(viewed_at)   AS day_name,
                    DAYOFWEEK(viewed_at) AS day_num,
                    COUNT(*)             AS vues
                FROM page_views
                WHERE viewed_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY day_num, day_name
                ORDER BY day_num ASC
            ");

            $frDays = [
                'Monday'    => 'Lun',
                'Tuesday'   => 'Mar',
                'Wednesday' => 'Mer',
                'Thursday'  => 'Jeu',
                'Friday'    => 'Ven',
                'Saturday'  => 'Sam',
                'Sunday'    => 'Dim',
            ];

            $result = [];
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($rows as $r) {
                $result[] = [
                    'day'  => $frDays[$r['day_name']] ?? $r['day_name'],
                    'vues' => (int) $r['vues'],
                ];
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Erreur getWeeklyActivity: " . $e->getMessage());
            return [];
        }
    }


    public function getTopMetiersRecherches(string $start, string $end, int $limit = 6): array
    {
        try {
        
            $stmt = $this->pdo->prepare("
                SELECT
                    metier_id,
                    metier_label AS name,
                    COUNT(*)     AS value
                FROM metier_searches
                WHERE searched_at BETWEEN :start AND :end
                GROUP BY metier_id, metier_label
                ORDER BY value DESC
                LIMIT :lim
            ");
            $stmt->bindValue(':start', $start);
            $stmt->bindValue(':end',   $end);
            $stmt->bindValue(':lim',   $limit, PDO::PARAM_INT);
            $stmt->execute();
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $interval   = (strtotime($end) - strtotime($start));
            $prevEnd    = date('Y-m-d H:i:s', strtotime($start) - 1);
            $prevStart  = date('Y-m-d H:i:s', strtotime($start) - $interval);

            $result = [];
            foreach ($rows as $r) {
                $stmtPrev = $this->pdo->prepare("
                    SELECT COUNT(*) FROM metier_searches
                    WHERE metier_id = :id
                      AND searched_at BETWEEN :start AND :end
                ");
                $stmtPrev->execute([
                    ':id'    => $r['metier_id'],
                    ':start' => $prevStart,
                    ':end'   => $prevEnd,
                ]);
                $prevCount = (int) $stmtPrev->fetchColumn();
                $current   = (int) $r['value'];

                $croissance = $prevCount > 0
                    ? round(($current - $prevCount) / $prevCount * 100, 0)
                    : ($current > 0 ? 100 : 0);

                $result[] = [
                    'metier_id'  => (int) $r['metier_id'],
                    'name'       => $r['name'],
                    'value'      => $current,
                    'croissance' => ($croissance >= 0 ? '+' : '') . $croissance . '%',
                ];
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Erreur getTopMetiersRecherches: " . $e->getMessage());
            return [];
        }
    }

    public function formatTopMetiersForChart(array $topMetiers): array
    {
        $formatted = [];
        foreach ($topMetiers as $metier) {
            $formatted[] = [
                'name' => $metier['name'],
                'value' => (int) $metier['value'],
                'croissance' => (string) $metier['croissance']
            ];
        }
        return $formatted;
    }


    public function getViewsByPage(string $start, string $end): array
    {
        try {
            $stmt = $this->pdo->prepare("
                SELECT page, COUNT(*) AS total
                FROM page_views
                WHERE viewed_at BETWEEN :start AND :end
                GROUP BY page
                ORDER BY total DESC
            ");
            $stmt->execute([':start' => $start, ':end' => $end]);
            
            $result = [];
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            foreach ($rows as $row) {
                $result[] = [
                    'page' => $row['page'],
                    'total' => (int) $row['total']
                ];
            }
            
            return $result;
        } catch (PDOException $e) {
            error_log("Erreur getViewsByPage: " . $e->getMessage());
            return [];
        }
    }
}
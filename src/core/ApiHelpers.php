<?php

class ApiHelpers
{
    public static function requestData(): array
    {
        $data = $_POST ?: [];
        $raw = file_get_contents('php://input');
        $json = json_decode($raw, true);

        if (is_array($json)) {
            $data = array_merge($data, $json);
        }

        return $data;
    }

    public static function paginationParams(): array
    {
        $page = max(1, (int) ($_GET['page'] ?? 1));
        $limit = max(1, (int) ($_GET['limit'] ?? 20));
        $limit = min($limit, 10000);

        return [$page, $limit];
    }

    public static function listResponse(string $resourceKey, array $items): array
    {
        [$page, $limit] = self::paginationParams();
        $total = count($items);
        $totalPages = max(1, (int) ceil($total / $limit));
        $offset = ($page - 1) * $limit;
        $data = array_values(array_slice($items, $offset, $limit));

        return [
            $resourceKey => $data,
            'data' => $data,
            'page' => $page,
            'limit' => $limit,
            'total' => $total,
            'totalPages' => $totalPages,
        ];
    }

    public static function filterItems(array $items, array $filters): array
    {
        $search = trim((string) ($_GET['search'] ?? ''));
        $filtered = $items;

        if ($search !== '') {
            $filtered = array_filter($filtered, function (array $item) use ($search) {
                foreach ($item as $value) {
                    if (self::valueContains($value, $search)) {
                        return true;
                    }
                }
                return false;
            });
        }

        foreach ($filters as $queryParam => $field) {
            $value = trim((string) ($_GET[$queryParam] ?? ''));
            if ($value === '') {
                continue;
            }

            $filtered = array_filter($filtered, function (array $item) use ($field, $value) {
                return self::valueContains($item[$field] ?? null, $value);
            });
        }

        $dateDebut = trim((string) ($_GET['dateDebut'] ?? ''));
        $dateFin = trim((string) ($_GET['dateFin'] ?? ''));
        if ($dateDebut !== '' || $dateFin !== '') {
            $filtered = array_filter($filtered, function (array $item) use ($dateDebut, $dateFin) {
                $dateValue = $item['created_at'] ?? $item['cree_le'] ?? $item['viewed_at'] ?? null;
                if (!$dateValue) {
                    return true;
                }

                $timestamp = strtotime((string) $dateValue);
                if (!$timestamp) {
                    return true;
                }

                if ($dateDebut !== '' && $timestamp < strtotime($dateDebut . ' 00:00:00')) {
                    return false;
                }

                if ($dateFin !== '' && $timestamp > strtotime($dateFin . ' 23:59:59')) {
                    return false;
                }

                return true;
            });
        }

        return array_values($filtered);
    }

    private static function valueContains(mixed $value, string $needle): bool
    {
        $needle = strtolower(trim($needle));

        if (is_array($value)) {
            foreach ($value as $item) {
                if (self::valueContains($item, $needle)) {
                    return true;
                }
            }
            return false;
        }

        if ($value === null) {
            return false;
        }

        return str_contains(strtolower((string) $value), $needle);
    }
}

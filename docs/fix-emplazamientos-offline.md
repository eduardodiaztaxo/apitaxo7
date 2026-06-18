# Fix: Emplazamientos offline — estandarización de filtros por `inv_inventario`

**Rama:** `fix/emplazamientoN4`  
**Commit:** `5b97d58`  
**Fecha:** 2026-06-17

## Problema

En modo offline (dump SQLite) se mostraban menos emplazamientos que en modo online. 
Ejemplo: 4 emplazamientos visibles online, solo 3 en el `.db` offline.

## Causa raíz

Los métodos del dump offline filtraban emplazamientos usando fuentes incorrectas:

| Método | Nivel | Filtraba por | Fuente |
|--------|-------|-------------|--------|
| `CycleCatsNivel1` | N1 | `EmplazamientosWithCatsN1()` | `crud_activos` + `inv_ciclos_categorias` |
| `showAllEmplaByCycleCatsWithFallback` | N2 | `zoneEmplazamientosWithCats()` | `crud_activos` + `inv_ciclos_categorias` |
| `CycleCatsNivel3` | N3 | `zoneSubEmplazamientosWithCats()` | `crud_activos` + `inv_ciclos_categorias` |
| `CycleCatsNn` | N4-N6 | `whereIn('idAgenda', $ids)` | puntos del ciclo |

`crud_activos` es el registro **teórico** de activos. `inv_inventario` es lo **contado** en el ciclo de inventario. Un emplazamiento puede tener activos contados sin tener registros en `crud_activos` que matcheen las categorías del ciclo.

Los fallbacks existentes eran inútiles en este escenario porque solo se activaban cuando la colección quedaba **vacía** (todo o nada). Si 3 de 4 matcheaban, el 4to se excluía silenciosamente.

## Solución

Estandarizar los 4 métodos con el mismo patrón:

1. Obtener **todos** los emplazamientos del nivel para el proyecto
2. Para cada uno, verificar si tiene registros en `inv_inventario` para el ciclo vía `inv_activos_with_child_levels()`
3. Fallback: si ninguno tiene, incluir todos

### Archivos modificados

| Archivo | Método | Cambio |
|---------|--------|--------|
| `app/Http/Controllers/Api/V1/InventariosOfflineController.php` | `CycleCatsNivel1` | `EmplazamientosWithCatsN1()` → `inv_activos_with_child_levels()` |
| `app/Http/Controllers/Api/V1/InventariosOfflineController.php` | `CycleCatsNivel3` | `zoneSubEmplazamientosWithCats()` → `inv_activos_with_child_levels()` |
| `app/Http/Controllers/Api/V1/InventariosOfflineController.php` | `CycleCatsNn` | `whereIn('idAgenda')` → `inv_activos_with_child_levels()` |
| `app/Http/Controllers/Api/V1/ZonaEmplazamientosController.php` | `showAllEmplaByCycleCatsWithFallback` | `zoneEmplazamientosWithCats()` → `inv_activos_with_child_levels()` |

### Patrón unificado

```php
$all = Modelo::where('idProyecto', $id_proyecto)->get();

$emplazamientos = collect();
foreach ($all as $item) {
    $tiene = $item->inv_activos_with_child_levels()
        ->where('inv_inventario.id_ciclo', $ciclo)
        ->where('inv_inventario.id_proyecto', $id_proyecto)
        ->exists();
    if ($tiene) {
        $item->cycle_id = $ciclo;
        $emplazamientos->push($item);
    }
}

if ($emplazamientos->isEmpty()) {
    foreach ($all as $item) {
        $item->cycle_id = $ciclo;
        $emplazamientos->push($item);
    }
}
```

## Impacto

- **Offline (dump SQLite):** corregido. Los emplazamientos ahora coinciden con lo visible online.
- **Online (API):** sin cambios. Los métodos de `ZonaEmplazamientosController` y `EmplazamientoController` usados por las rutas API no fueron tocados.

## Modelos que soportan `inv_activos_with_child_levels()`

| Modelo | Tabla | Nivel |
|--------|-------|-------|
| `EmplazamientoN1` | `ubicaciones_n1` | N1 |
| `Emplazamiento` | `ubicaciones_n2` | N2 |
| `EmplazamientoN3` | `ubicaciones_n3` | N3 |
| `EmplazamientoNn` | `ubicaciones_n{level}` (dinámico) | N4-N6 |

## Comandos de dump afectados

- `php artisan command:export-for-offline-inventory-sqlite --connection=X --cycle=Y`
- `php artisan command:export-for-offline-audit-sqlite --connection=X --cycle=Y`

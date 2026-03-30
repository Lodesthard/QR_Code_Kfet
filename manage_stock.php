<?php
    session_start();

    require_once('lib/redirect.php');
    auth_level(1);

    require_once('lib/connect.php');
    $mysqli = connectToDatabase();
?>

<!DOCTYPE html>
<html>
<head>
    <?php include('templates/head.php') ?>
    <title>Gestion des stocks</title>
    <link rel="stylesheet" type="text/css" href="css/administrate.css">
    <style>
        .stock-table { max-width: 800px; margin: 1rem auto; }
        .stock-cell { white-space: nowrap; }
        .stock-value { font-weight: bold; min-width: 2rem; display: inline-block; text-align: center; transition: transform 0.1s; }
        .stock-low { color: #dc3545; }
        .stock-ok { color: #28a745; }
        .stock-warn { color: #ffc107; }
        .stock-bump { transform: scale(1.3); }
        .search-bar { max-width: 400px; margin: 0 auto 1rem; }
        .btn-stock { min-width: 36px; font-weight: bold; font-size: 1.1rem; }
        .qty-input { width: 60px !important; display: inline-block !important; text-align: center; }
    </style>
</head>
<body>

    <?php include('templates/nav.php') ?>

    <main>
        <h2 class="text-center mt-3">Gestion des stocks</h2>

        <div class="search-bar">
            <input type="text" id="stock-search" class="form-control" placeholder="Rechercher un produit...">
        </div>

        <div class="stock-table">
            <table class="table table-hover table-sm table-striped bg-light" id="stock-table">
                <thead class="thead-dark">
                    <tr>
                        <th>Produit</th>
                        <th>Catégorie</th>
                        <th class="text-center">Stock</th>
                        <th class="text-center">Modifier</th>
                    </tr>
                </thead>
                <tbody>
<?php
    $categories = [0 => 'Boisson chaude', 1 => 'Boisson froide', 2 => 'Snack', 3 => 'Formule'];

    $result = $mysqli->query('SELECT id, name, category, stock FROM products ORDER BY name ASC');
    while($product = $result->fetch_assoc()):
        $stock = (int)$product['stock'];
        $stockClass = 'stock-ok';
        if($stock <= 0) $stockClass = 'stock-low';
        else if($stock <= 5) $stockClass = 'stock-warn';
        $catName = isset($categories[$product['category']]) ? $categories[$product['category']] : 'Autre';
        $pid = (int)$product['id'];
?>
                    <tr>
                        <td><?php echo htmlspecialchars($product['name']); ?></td>
                        <td><small class="text-muted"><?php echo $catName; ?></small></td>
                        <td class="text-center">
                            <span class="stock-value <?php echo $stockClass; ?>" id="stock-<?php echo $pid; ?>"><?php echo $stock; ?></span>
                        </td>
                        <td class="text-center stock-cell">
                            <button class="btn btn-outline-danger btn-sm btn-stock" onclick="updateStock(<?php echo $pid; ?>, -1)">-</button>
                            <button class="btn btn-outline-success btn-sm btn-stock" onclick="updateStock(<?php echo $pid; ?>, 1)">+</button>
                            <input type="number" id="qty-<?php echo $pid; ?>" class="form-control form-control-sm qty-input ml-2" placeholder="Qté">
                            <button class="btn btn-outline-primary btn-sm" onclick="updateStockCustom(<?php echo $pid; ?>)">OK</button>
                        </td>
                    </tr>
<?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </main>

<script>
function updateStock(productId, quantity) {
    const body = new URLSearchParams();
    body.append('product_id', productId);
    body.append('quantity', quantity);

    fetch('lib/admin/add_stock.php', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest' },
        body: body
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            const el = document.getElementById('stock-' + productId);
            el.textContent = data.stock;

            // Update color
            el.className = 'stock-value ' + (data.stock <= 0 ? 'stock-low' : data.stock <= 5 ? 'stock-warn' : 'stock-ok');

            // Quick visual feedback
            el.classList.add('stock-bump');
            setTimeout(() => el.classList.remove('stock-bump'), 150);
        }
    })
    .catch(() => {});
}

function updateStockCustom(productId) {
    const input = document.getElementById('qty-' + productId);
    const qty = parseInt(input.value);
    if (isNaN(qty) || qty === 0) return;
    updateStock(productId, qty);
    input.value = '';
}

document.getElementById('stock-search').addEventListener('input', function() {
    const filter = this.value.toLowerCase();
    document.querySelectorAll('#stock-table tbody tr').forEach(row => {
        row.style.display = row.cells[0].textContent.toLowerCase().includes(filter) ? '' : 'none';
    });
});
</script>
</body>
</html>
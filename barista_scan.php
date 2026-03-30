<?php
    session_start();

    require_once('lib/redirect.php');
    auth_level(1);
?>
<!DOCTYPE html>
<html>
<head>
    <title>Kfet – Scanner une commande</title>
    <?php include 'templates/head.php'; ?>
    <link rel="stylesheet" href="css/barista_scan.css">
</head>
<body>
<?php include 'templates/nav.php'; ?>

<div id="container">
    <main class="scan-wrapper">

        <!-- État : caméra active -->
        <div id="scan-section">
            <h2 class="text-center mb-1">Scanner une commande</h2>
            <p class="text-center text-muted mb-3">Pointez la caméra vers le QR code de l'étudiant·e.</p>

            <div id="reader-container">
                <div id="reader"></div>
            </div>

            <div class="text-center mt-3">
                <p class="text-muted small">Ou entrez le token manuellement :</p>
                <div class="input-group manual-input">
                    <input type="text" id="manual-token" class="form-control" placeholder="Token de commande">
                    <div class="input-group-append">
                        <button class="btn btn-primary" onclick="loadOrderByToken(document.getElementById('manual-token').value)">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- État : commande trouvée -->
        <div id="order-section" class="d-none">

            <div class="order-card">
                <div class="order-card-header">
                    <span id="order-status-badge" class="badge badge-warning">En attente</span>
                    <h4 id="order-username" class="mt-2 mb-0"></h4>
                    <small id="order-id" class="text-muted"></small>
                </div>

                <div class="order-items" id="order-items-list"></div>

                <div class="order-card-footer">
                    <div class="total-line">
                        <span>Total</span>
                        <strong id="order-total"></strong>
                    </div>

                    <button id="btn-done" class="btn btn-success btn-block mt-3" onclick="markOrderDone()">
                        <i class="fas fa-check mr-2"></i>Commande servie
                    </button>
                    <button class="btn btn-outline-secondary btn-block mt-2" onclick="resetScanner()">
                        <i class="fas fa-qrcode mr-2"></i>Scanner une autre commande
                    </button>
                </div>
            </div>

        </div>

        <!-- État : erreur -->
        <div id="error-section" class="d-none text-center">
            <i class="fas fa-times-circle error-icon"></i>
            <p id="error-message" class="mt-2 text-danger"></p>
            <button class="btn btn-outline-secondary mt-2" onclick="resetScanner()">
                <i class="fas fa-redo mr-1"></i>Réessayer
            </button>
        </div>

    </main>
</div>

<!-- html5-qrcode -->
<script src="https://unpkg.com/html5-qrcode@2.3.8/html5-qrcode.min.js"></script>
<script src="js/barista_scan.js"></script>
</body>
</html>

<?php
require_once 'config/config.php';

// Récupérer les données brutes de la requête POST
$input = file_get_contents('php://input');
$data = json_decode($input, true);

// Vérifier si les données sont valides
if (!$data || !isset($data['ref_command']) || !isset($data['token']) || !isset($data['type'])) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Données invalides']);
    exit;
}

// Récupérer les informations de la notification
$ref_payment = $data['ref_command'];
$token = $data['token'];
$type = $data['type'];

// Journaliser la notification IPN pour le débogage
$log_file = fopen('logs/paytech_ipn.log', 'a');
fwrite($log_file, date('Y-m-d H:i:s') . " - Notification reçue: " . $input . "\n");
fclose($log_file);

// Vérifier le type de notification
if ($type === 'payment_success') {
    try {
        // Trouver la réservation correspondante
        $query = "SELECT * FROM reservations WHERE reference_paiement = :reference";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':reference', $ref_payment);
        $stmt->execute();
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($reservation) {
            // Mettre à jour le statut de paiement
            $query = "UPDATE reservations SET statut_paiement = 'paye', date_paiement = NOW() WHERE id = :reservation_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':reservation_id', $reservation['id']);
            $stmt->execute();
            
            // Réponse de succès
            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => 'Paiement traité avec succès']);
        } else {
            // Réservation non trouvée
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Réservation non trouvée']);
        }
    } catch (PDOException $e) {
        // Erreur de base de données
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
    }
} elseif ($type === 'payment_canceled') {
    try {
        // Trouver la réservation correspondante
        $query = "SELECT * FROM reservations WHERE reference_paiement = :reference";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':reference', $ref_payment);
        $stmt->execute();
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($reservation) {
            // Mettre à jour le statut de paiement
            $query = "UPDATE reservations SET statut_paiement = 'annule' WHERE id = :reservation_id";
            $stmt = $db->prepare($query);
            $stmt->bindParam(':reservation_id', $reservation['id']);
            $stmt->execute();
            
            // Réponse de succès
            http_response_code(200);
            echo json_encode(['status' => 'success', 'message' => 'Annulation traitée avec succès']);
        } else {
            // Réservation non trouvée
            http_response_code(404);
            echo json_encode(['status' => 'error', 'message' => 'Réservation non trouvée']);
        }
    } catch (PDOException $e) {
        // Erreur de base de données
        http_response_code(500);
        echo json_encode(['status' => 'error', 'message' => 'Erreur de base de données: ' . $e->getMessage()]);
    }
} else {
    // Type de notification non pris en charge
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Type de notification non pris en charge']);
}
?>

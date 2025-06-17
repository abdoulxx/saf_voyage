<?php
session_start();
require_once 'config/config.php';
require_once 'fpdf/fpdf.php';

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

// Vérifier si l'ID de réservation est fourni
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: mes_reservations.php");
    exit;
}

$reservation_id = intval($_GET['id']);

// Récupérer les détails de la réservation
try {
    $query = "SELECT r.*, v.destination, v.date_depart, v.date_retour, v.duree, 
              h.nom as hotel_nom, h.localisation as hotel_localisation, h.prix_nuit as hotel_prix,
              u.nom as user_nom, u.email as user_email, u.numero_telephone as user_telephone
              FROM reservations r
              LEFT JOIN vols v ON r.vol_id = v.id
              LEFT JOIN hotels h ON r.hotel_id = h.id
              LEFT JOIN users u ON r.user_id = u.id
              WHERE r.id = :reservation_id AND r.user_id = :user_id";
    
    $stmt = $db->prepare($query);
    $stmt->bindParam(':reservation_id', $reservation_id);
    $stmt->bindParam(':user_id', $_SESSION['user_id']);
    $stmt->execute();
    $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$reservation) {
        $_SESSION['error'] = "Réservation introuvable.";
        header("Location: mes_reservations.php");
        exit;
    }
    
    // Récupérer les options de la réservation
    $query = "SELECT option_nom FROM reservation_options WHERE reservation_id = :reservation_id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':reservation_id', $reservation_id);
    $stmt->execute();
    $options = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // Récupérer les prix des options
    $options_prix = [];
    if (!empty($options)) {
        $placeholders = implode(',', array_fill(0, count($options), '?'));
        $query = "SELECT nom, prix FROM options WHERE nom IN ($placeholders)";
        $stmt = $db->prepare($query);
        foreach ($options as $key => $option) {
            $stmt->bindValue($key + 1, $option);
        }
        $stmt->execute();
        $options_data = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($options_data as $option) {
            $options_prix[$option['nom']] = $option['prix'];
        }
    }
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Erreur lors de la récupération des détails de la réservation: " . $e->getMessage();
    header("Location: mes_reservations.php");
    exit;
}

// Créer le PDF
class PDF extends FPDF
{
    function Header()
    {
        // Logo à gauche
        $this->Image('assets/images/logo.jpg', 10, 10, 30);
        
        // Informations de l'entreprise à droite
        $this->SetFont('Arial', 'B', 12);
        $this->SetXY(140, 10);
        $this->Cell(60, 6, 'Saf Voyage', 0, 1, 'R');
        $this->SetFont('Arial', '', 10);
        $this->SetXY(140, 16);
        $this->Cell(60, 6, '123 Rue du Voyage, abidjan', 0, 1, 'R');
        $this->SetXY(140, 22);
        $this->Cell(60, 6, 'Tel: +225 XX XXX XX XX', 0, 1, 'R');
        $this->SetXY(140, 28);
        $this->Cell(60, 6, 'contact@safvoyage.com', 0, 1, 'R');
        
        // Titre de la facture
        $this->SetFont('Arial', 'B', 16);
        $this->SetXY(10, 45);
        $this->Cell(190, 10, 'FACTURE', 0, 1, 'C');
        
        // Ligne de séparation
        $this->SetDrawColor(200, 200, 200);
        $this->Line(10, 55, 200, 55);
        
        // Saut de ligne
        $this->Ln(10);
    }
    
    function Footer()
    {
        // Positionnement à 1,5 cm du bas
        $this->SetY(-15);
        // Police Arial italique 8
        $this->SetFont('Arial', 'I', 8);
        // Numéro de page
        $this->Cell(0, 10, 'Page ' . $this->PageNo() . '/{nb}', 0, 0, 'C');
    }
}

// Instanciation de la classe dérivée
$pdf = new PDF();
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetFont('Arial', '', 10);

// Informations du client et de la facture
$pdf->SetXY(10, 60);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(95, 6, 'Facture à:', 0, 0);
$pdf->SetXY(105, 60);
$pdf->Cell(95, 6, 'Details de la facture:', 0, 0);

// Informations du client
$pdf->SetFont('Arial', '', 9);
$pdf->SetXY(10, 66);
$pdf->Cell(95, 5, 'Nom: ' . $reservation['user_nom'], 0, 0);
$pdf->SetXY(10, 71);
$pdf->Cell(95, 5, 'Email: ' . $reservation['user_email'], 0, 0);
$pdf->SetXY(10, 76);
$pdf->Cell(95, 5, 'Tel: ' . $reservation['user_telephone'], 0, 0);

// Informations de la facture
$pdf->SetXY(105, 66);
$pdf->Cell(95, 5, 'Numero de Facture: F-' . $reservation_id, 0, 0);
$pdf->SetXY(105, 71);
$pdf->Cell(95, 5, 'Date: ' . date('d/m/Y', strtotime($reservation['date_reservation'])), 0, 0);
$pdf->SetXY(105, 76);
$pdf->Cell(95, 5, 'Statut: ' . ($reservation['statut_paiement'] === 'paye' ? 'paye' : 'En attente'), 0, 0);

// Détails de la réservation
$pdf->Ln(15);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 6, 'Details de la reservation:', 0, 1);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(0, 5, 'Destination: ' . ucfirst($reservation['destination']), 0, 1);
$pdf->Cell(0, 5, 'Dates: Du ' . date('d/m/Y', strtotime($reservation['date_depart'])) . ' au ' . date('d/m/Y', strtotime($reservation['date_retour'])), 0, 1);
$pdf->Cell(0, 5, 'Duree: ' . $reservation['duree'] . ' jours', 0, 1);

if ($reservation['hotel_id']) {
    $pdf->Cell(0, 5, 'Hotel: ' . $reservation['hotel_nom'] . ' (' . $reservation['hotel_localisation'] . ')', 0, 1);
}

// Tableau des prix
$pdf->Ln(5);
$pdf->SetFont('Arial', 'B', 10);
$pdf->Cell(0, 6, 'Detail des prix:', 0, 1);
$pdf->SetFillColor(240, 240, 240);
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(100, 6, 'Description', 1, 0, 'C', true);
$pdf->Cell(40, 6, 'Prix unitaire', 1, 0, 'C', true);
$pdf->Cell(20, 6, 'Qte', 1, 0, 'C', true);
$pdf->Cell(30, 6, 'Total', 1, 1, 'C', true);

$pdf->SetFont('Arial', '', 9);

// Vol
$vol_query = "SELECT prix FROM vols WHERE id = :vol_id";
$stmt = $db->prepare($vol_query);
$stmt->bindParam(':vol_id', $reservation['vol_id']);
$stmt->execute();
$vol_prix = $stmt->fetchColumn();

$pdf->Cell(100, 6, 'Vol - ' . ucfirst($reservation['destination']), 1, 0, 'L');
$pdf->Cell(40, 6, number_format($vol_prix, 0, ',', ' ') . ' FCFA', 1, 0, 'R');
$pdf->Cell(20, 6, '1', 1, 0, 'C');
$pdf->Cell(30, 6, number_format($vol_prix, 0, ',', ' ') . ' FCFA', 1, 1, 'R');

// Hôtel
if ($reservation['hotel_id']) {
    $pdf->Cell(100, 6, 'Hotel - ' . $reservation['hotel_nom'], 1, 0, 'L');
    $pdf->Cell(40, 6, number_format($reservation['hotel_prix'], 0, ',', ' ') . ' FCFA', 1, 0, 'R');
    $pdf->Cell(20, 6, $reservation['duree'], 1, 0, 'C');
    $pdf->Cell(30, 6, number_format($reservation['hotel_prix'] * $reservation['duree'], 0, ',', ' ') . ' FCFA', 1, 1, 'R');
}

// Options
foreach ($options as $option) {
    if (isset($options_prix[$option])) {
        $pdf->Cell(100, 6, 'Option - ' . $option, 1, 0, 'L');
        $pdf->Cell(40, 6, number_format($options_prix[$option], 0, ',', ' ') . ' FCFA', 1, 0, 'R');
        $pdf->Cell(20, 6, $reservation['duree'], 1, 0, 'C');
        $pdf->Cell(30, 6, number_format($options_prix[$option] * $reservation['duree'], 0, ',', ' ') . ' FCFA', 1, 1, 'R');
    }
}

// Total
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(160, 6, 'Total', 1, 0, 'R', true);
$pdf->Cell(30, 6, number_format($reservation['prix_total'], 0, ',', ' ') . ' FCFA', 1, 1, 'R', true);

// Informations de paiement
$pdf->Ln(5);
$pdf->SetFont('Arial', 'B', 9);
$pdf->Cell(0, 5, 'Informations de paiement:', 0, 1);
$pdf->SetFont('Arial', '', 9);
$pdf->Cell(0, 5, 'Methode: ' . ($reservation['methode_paiement'] === 'reception' ? 'Paiement a la reception' : 'Paiement en ligne'), 0, 1);

if ($reservation['statut_paiement'] === 'paye' && $reservation['date_paiement']) {
    $pdf->Cell(0, 5, 'Date de paiement: ' . date('d/m/Y H:i', strtotime($reservation['date_paiement'])), 0, 1);
}

// Remerciements
$pdf->Ln(5);
$pdf->SetFont('Arial', 'I', 8);
$pdf->MultiCell(0, 4, 'Merci d\'avoir choisi Saf Voyage. Pour toute question, contactez-nous a contact@safvoyage.com');

// Générer le PDF
$pdf->Output('D', 'Facture_Saf_Voyage_' . $reservation_id . '.pdf');
?>

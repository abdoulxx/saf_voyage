<?php
session_start();
require 'config/config.php'; // Connexion PDO
require('fpdf/fpdf.php');    // Inclusion de la librairie FPDF

// 1) Vérifier que l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}
$user_id = $_SESSION['user_id'];

/**
 * Récupération de l'ID de réservation passé en GET, ex: ?id=123
 * (On clique sur "Imprimer" depuis mes_reservations.php)
 */
if (!isset($_GET['id'])) {
    echo "Erreur : aucune réservation spécifiée.";
    exit;
}
$reservation_id = (int) $_GET['id'];

/**
 * 2) Récupérer la réservation en BDD
 *    + Vérifier qu'elle appartient bien à user_id
 */
$sql = "SELECT r.*,
               u.nom AS user_nom,
               u.prenom AS user_prenom,
               u.email AS user_email,
               u.numero_telephone AS user_phone
        FROM reservations r
        JOIN users u ON r.user_id = u.id
        WHERE r.id = :res_id
          AND r.user_id = :user_id
        LIMIT 1";
$stmt = $db->prepare($sql);
$stmt->execute([
    ':res_id'  => $reservation_id,
    ':user_id' => $user_id
]);
$reservation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reservation) {
    echo "Erreur : réservation introuvable ou ne vous appartient pas.";
    exit;
}

// Extraire les infos
$nom_client    = $reservation['user_nom'] . ' ' . $reservation['user_prenom'];
$numero_tel    = $reservation['user_phone'];
$email_client  = $reservation['user_email'];
$numero_chambre= $reservation['numero_chambre'];
$type_chambre  = $reservation['type_chambre'];
$date_debut    = $reservation['date_debut'];
$date_fin      = $reservation['date_fin'];
$prix_total    = $reservation['prix_total'];
$statut        = $reservation['statut'];
$created_at    = $reservation['created_at']; // Date de création de la réservation, par ex.
$id_resa       = $reservation['id'];

/**
 * 3) Créer le PDF avec FPDF
 */
$pdf = new FPDF();
$pdf->AddPage();

/** 
 * Option : vous pouvez configurer des marges, orientation, etc.
 * ex: $pdf = new FPDF('P','mm','A4'); 
 */

/**
 * 4) Insérer le logo
 *    - $pdf->Image('chemin_du_logo.png', x, y, largeur)
 *    - x=10, y=8 => marges
 *    - Largeur=30 (exemple)
 */
$pdf->Image('fpdf/logo.png', 10, 8, 30); // Logo


/**
 * 5) Police et Titre du document
 */
$pdf->SetFont('Arial','B',14);
// Cell(larg, haut, texte, border, ln, align)
$pdf->Cell(0,10,("Reçu de la transaction"),0,1,'C');
$pdf->Ln(5); // Saut de 5 mm

/**
 * 6) Afficher l'adresse ou infos de l'hôtel à droite (exemple)
 */
$pdf->SetFont('Arial','',10);
$pdf->Cell(0,5,("HOTEL XYZ, Abidjan, Côte d'Ivoire"),0,1,'R');
$pdf->Cell(0,5,("Téléphone : +225 XX XX XX XX"),0,1,'R');
$pdf->Cell(0,5,("Email : hotelxyz@example.com"),0,1,'R');

$pdf->Ln(10); // Saut

// Trait de séparation
$pdf->Cell(0,0,'','B',1,'C');
$pdf->Ln(5);

/**
 * 7) Section "Mes informations" (client)
 */
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,6,("Mes informations"),0,1);

$pdf->SetFont('Arial','',11);

$pdf->Cell(50,6,("Nom et prénoms : "),0,0);
$pdf->Cell(0,6,($nom_client),0,1);

$pdf->Cell(50,6,("Numéro téléphone : "),0,0);
$pdf->Cell(0,6,($numero_tel),0,1);

$pdf->Cell(50,6,"Email : ",0,0);
$pdf->Cell(0,6,($email_client),0,1);

$pdf->Ln(8);

/**
 * 8) Section "Transaction" (informations sur la réservation)
 */
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,6,("Transaction"),0,1);

$pdf->SetFont('Arial','',11);

// Exemple: type de chambre, date, statut...
$pdf->Cell(50,6,("Chambre : "),0,0);
$pdf->Cell(0,6,($numero_chambre." (".$type_chambre.")"),0,1);

$pdf->Cell(50,6,("Date de transaction : "),0,0);
// On formate la date s'il y a lieu
$format_created = date("d/m/Y H:i", strtotime($created_at));
$pdf->Cell(0,6,($format_created),0,1);

$pdf->Cell(50,6,("Statut : "),0,0);
$pdf->SetTextColor(255,0,0); // par ex. rouge si en attente
$pdf->Cell(0,6,($statut),0,1);
$pdf->SetTextColor(0,0,0); // reset couleur

$pdf->Ln(8);

/**
 * 9) Section "Détails de la transaction"
 */
$pdf->SetFont('Arial','B',12);
$pdf->Cell(0,6,("Détails de la transaction :"),0,1);

$pdf->SetFont('Arial','',11);

// Montant
$pdf->Cell(50,6,("Montant :"),0,0);
$montant_fmt = number_format($prix_total, 0, ',', ' ') . " FCFA";
$pdf->Cell(0,6,($montant_fmt),0,1);

// Dates de commande / fin (dans l'exemple)
$pdf->Cell(50,6,("Date d'arrivée :"),0,0);
$pdf->Cell(0,6,($date_debut),0,1);

$pdf->Cell(50,6,("Date de départ :"),0,0);
$pdf->Cell(0,6,($date_fin),0,1);

/**
 * 10) Bas de page / Remerciements
 */
$pdf->Ln(10);
$pdf->SetFont('Arial','I',10);
$pdf->Cell(0,6,("Merci pour votre confiance!"),0,1,'C');

$pdf->Ln(8);
/**
 * 11) Pied de page possible (ex: un trait + ID transaction)
 */
$pdf->Cell(0,0,'','B',1,'C');
$pdf->Ln(2);
$pdf->SetFont('Arial','',8);
$pdf->Cell(0,5,("ID de la transaction : #".$id_resa),0,1,'C');

/**
 * 12) Sortie du PDF
 *    - "I" = envoi direct au navigateur (inline)
 *    - "facture.pdf" = nom suggéré au navigateur
 */
$pdf->Output('I','facture_'.$id_resa.'.pdf');
exit;

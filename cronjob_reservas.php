<?php
include("conex.php");

date_default_timezone_set('America/Argentina/Cordoba');

// Check for users with auto-reservation option and available credits
$eligibleUsersQuery = mysqli_query($mysqli, "SELECT * FROM registrados WHERE auto_reservation_option = 1 AND credito >= minimum_credit_required");

while ($user = mysqli_fetch_assoc($eligibleUsersQuery)) {
    // Check if the user has made reservations in the previous week
    $lastWeek = strtotime('-1 week');
    $reservationsLastWeek = mysqli_query($mysqli, "SELECT * FROM actividad_reservas WHERE registrados_dni='" . $user['dni'] . "' AND fecha >= '" . date('Y-m-d', $lastWeek) . "'");

    if (mysqli_num_rows($reservationsLastWeek) > 0) {
        // User made reservations last week, automate reservations for the same time slots

        // Iterate through previous week's reservations
        while ($row = mysqli_fetch_assoc($reservationsLastWeek)) {
            $id_horario = $row['actividad_horarios_id_horario'];
            $fecha = $row['fecha'];

            // Check if the reservation already exists for the current week
            $existingReservation = mysqli_query($mysqli, "SELECT * FROM actividad_reservas WHERE registrados_dni='" . $user['dni'] . "' AND fecha='" . $fecha . "' AND actividad_horarios_id_horario='" . $id_horario . "'");
            if (mysqli_num_rows($existingReservation) == 0) {
                // Check if the id_horario is listed in actividad_horarios_susp (holiday or suspended activity)
                $isSuspended = mysqli_query($mysqli, "SELECT * FROM actividad_horarios_susp WHERE id_horario='" . $id_horario . "' AND fecha='" . $fecha . "'");

                if (mysqli_num_rows($isSuspended) == 0) {
                    // Check if the user has enough credits for the reservation
                    $cost = getReservationCost($mysqli, $id_horario); // Assuming there is a function to retrieve the cost

                    if ($user['credito'] >= $cost) {
                        // Reserve the same time slot for the current week
                        mysqli_query($mysqli, "INSERT INTO actividad_reservas (registrados_dni, fecha, actividad_horarios_id_horario) VALUES ('" . $user['dni'] . "', '" . $fecha . "', '" . $id_horario . "')");

                        // Deduct the cost from the user's credits
                        mysqli_query($mysqli, "UPDATE registrados SET credito = credito - " . $cost . " WHERE dni = '" . $user['dni'] . "'");
                    }
                }
            }
        }
    }
}

function getReservationCost($mysqli, $id_horario) {
    // Implement the logic to retrieve the reservation cost based on $id_horario
    // For example, you might have a table with activity costs, and you can fetch the cost using a query
    $costQuery = mysqli_query($mysqli, "SELECT cost FROM activity_costs WHERE id_horario = '" . $id_horario . "'");
    $costRow = mysqli_fetch_assoc($costQuery);

    return $costRow['cost'];
}

?>

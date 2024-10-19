<?php

ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

// Route สำหรับการจัดการผู้ใช้
$app->get('/admin/users', function (Request $request, Response $response) {
    $conn = $GLOBALS['connect'];
    $sql = 'SELECT * FROM Users';
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);

    $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK));
    return $response
        ->withHeader('Content-Type', 'application/json; charset=utf-8')
        ->withStatus(200);
});

// บูธว่าง
$app->get('/booths/available', function (Request $request, Response $response) {
    $conn = $GLOBALS['connect'];
    $sql = "SELECT * FROM Booths WHERE booth_status = 'ว่าง'";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);

    $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK));
    return $response
        ->withHeader('Content-Type', 'application/json; charset=utf-8')
        ->withStatus(200);
});

// Route สำหรับดึงข้อมูล events
$app->get('/admin/events', function (Request $request, Response $response) {
    $conn = $GLOBALS['connect'];
    $sql = "SELECT * FROM Events";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);

    $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK));
    return $response
        ->withHeader('Content-Type', 'application/json; charset=utf-8')
        ->withStatus(200);
});

// เพิ่มข้อมูล events
$app->post('/admin/events', function (Request $request, Response $response) {
    $jsonData = json_decode($request->getBody(), true);

    if (isset(
        $jsonData['event_name'],
        $jsonData['event_start_date'],
        $jsonData['event_end_date']
    )) {
        $conn = $GLOBALS['connect'];
        $sql = "INSERT INTO Events (
        event_name, 
        event_start_date, 
        event_end_date) 
        VALUES (?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            'sss',
            $jsonData['event_name'],
            $jsonData['event_start_date'],
            $jsonData['event_end_date']
        );


        if ($stmt->execute()) {
            $data = ["affected_rows" => $stmt->affected_rows, "last_idx" => $conn->insert_id];
            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } else {
            $data = ["error" => "Failed to insert data"];
            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    } else {
        $data = ["error" => "Invalid input"];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
});

// ลบ events
$app->delete('/admin/events/{id}', function (Request $request, Response $response, $args) {
    $id = $args['id'];
    $conn = $GLOBALS['connect'];
    $sql = "DELETE FROM Events WHERE event_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $data = ["affected_rows" => $stmt->affected_rows];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } else {
        $data = ["error" => "No such event found"];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }
});

// แก้ไข events
$app->put('/admin/events/{id}', function (Request $request, Response $response, $args) {
    $id = $args['id'];
    $jsonData = json_decode($request->getBody(), true);

    if (isset(
        $jsonData['event_name'],
        $jsonData['event_start_date'],
        $jsonData['event_end_date']
    )) {
        $conn = $GLOBALS['connect'];
        $sql = "UPDATE Events SET 
        event_name = ?, 
        event_start_date = ?, 
        event_end_date = ? 
        WHERE event_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            'sssi',
            $jsonData['event_name'],
            $jsonData['event_start_date'],
            $jsonData['event_end_date'],
            $id
        );

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $data = ["affected_rows" => $stmt->affected_rows];
            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } else {
            $data = ["error" => "No such event found"];
            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
    } else {
        $data = ["error" => "Invalid input"];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
});

// เพิ่มข้อมูลบูธ
$app->post('/admin/booths', function (Request $request, Response $response) {
    $jsonData = json_decode($request->getBody(), true);

    if (isset(
        $jsonData['booth_name'],
        $jsonData['booth_size'],
        $jsonData['booth_price'],
        $jsonData['booth_image'],
        $jsonData['zone_id']
    )) {
        $conn = $GLOBALS['connect'];
        $sql = "INSERT INTO Booths 
        (booth_name, 
        booth_size, 
        booth_status, 
        booth_price, 
        booth_image, 
        zone_id) 
        VALUES (?, ?, 'ว่าง', ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            'ssdss',
            $jsonData['booth_name'],
            $jsonData['booth_size'],
            $jsonData['booth_price'],
            $jsonData['booth_image'],
            $jsonData['zone_id']
        );

        if ($stmt->execute()) {
            $data = ["affected_rows" => $stmt->affected_rows, "last_idx" => $conn->insert_id];
            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } else {
            $data = ["error" => "Failed to insert data"];
            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    } else {
        $data = ["error" => "Invalid input"];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
});

// แก้ไขบูธ
$app->put('/admin/booths/{id}', function (Request $request, Response $response, $args) {
    $id = $args['id'];
    $jsonData = json_decode($request->getBody(), true);

    if (isset(
        $jsonData['booth_name'],
        $jsonData['booth_size'],
        $jsonData['booth_price'],
        $jsonData['booth_image'],
        $jsonData['zone_id']
    )) {
        $conn = $GLOBALS['connect'];
        $sql = "UPDATE Booths SET 
        booth_name = ?, 
        booth_size = ?, 
        booth_price = ?, 
        booth_image = ?, 
        zone_id = ? 
        WHERE booth_id = ?";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            'ssdssi',
            $jsonData['booth_name'],
            $jsonData['booth_size'],
            $jsonData['booth_price'],
            $jsonData['booth_image'],
            $jsonData['zone_id'],
            $id
        );

        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $data = ["affected_rows" => $stmt->affected_rows];
            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } else {
            $data = ["error" => "No such booth found or no changes made"];
            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
    } else {
        $data = ["error" => "Invalid input"];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
});

// ลบบูธ
$app->delete('/admin/booths/{id}', function (Request $request, Response $response, $args) {
    $id = $args['id'];
    $conn = $GLOBALS['connect'];
    $sql = "DELETE FROM Booths WHERE booth_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $id);
    $stmt->execute();

    if ($stmt->affected_rows > 0) {
        $data = ["affected_rows" => $stmt->affected_rows];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } else {
        $data = ["error" => "No such booth found"];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }
});

// จองบูธ
$app->post('/booking', function (Request $request, Response $response) {
    $jsonData = json_decode($request->getBody(), true);

    if (isset(
        $jsonData['booth_id'],
        $jsonData['user_id']
    )) {
        $conn = $GLOBALS['connect'];
        $sql = "INSERT INTO Bookings 
        (booth_id, 
        user_id) 
        VALUES (?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param(
            'ii',
            $jsonData['booth_id'],
            $jsonData['user_id']
        );

        if ($stmt->execute()) {
            $data = ["affected_rows" => $stmt->affected_rows, "last_idx" => $conn->insert_id];
            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
        } else {
            $data = ["error" => "Failed to book the booth"];
            $response->getBody()->write(json_encode($data));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
        }
    } else {
        $data = ["error" => "Invalid input"];
        $response->getBody()->write(json_encode($data));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }
});

// จองบูธสำหรับผู้ใช้
$app->get('/admin/{user_id}/bookings', function (Request $request, Response $response, $args) {
    $user_id = $args['user_id'];
    $conn = $GLOBALS['connect'];
    $sql = "SELECT * FROM Bookings WHERE user_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param('i', $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);

    $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK));
    return $response
        ->withHeader('Content-Type', 'application/json; charset=utf-8')
        ->withStatus(200);
});

// Get zones all
$app->get('/zones', function (Request $request, Response $response) {
    $conn = $GLOBALS['connect'];
    $sql = "SELECT * FROM Zones";
    $stmt = $conn->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $data = $result->fetch_all(MYSQLI_ASSOC);

    $response->getBody()->write(json_encode($data, JSON_UNESCAPED_UNICODE | JSON_NUMERIC_CHECK));
    return $response
        ->withHeader('Content-Type', 'application/json; charset=utf-8')
        ->withStatus(200);
});



$app->put('/admin/bookings/{id}/approve', function (Request $request, Response $response, $args) {
    $id = $args['id'];

    $conn = $GLOBALS['connect'];

    // ตรวจสอบสถานะการจองจากฐานข้อมูล

    $sql_check_booking = "SELECT booking_status, booth_id FROM Bookings WHERE booking_id = ?";
    $stmt_check = $conn->prepare($sql_check_booking);
    $stmt_check->bind_param("i", $id);
    $stmt_check->execute();


    $booking_status = NUll;
    $booth_id = NULL;

    $stmt_check->bind_result($booking_status, $booth_id);
    $stmt_check->fetch();
    $stmt_check->close();

    // ไม่เจอการจอง
    if (empty($booking_status)) {
        $response->getBody()->write(json_encode(['error' => 'ไม่พบการจอง']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    // สถานะการจองไม่ใช่ "ชำระเงินแล้ว"
    if ($booking_status !== 'ชำระเงินแล้ว') {
        $response->getBody()->write(json_encode(['error' => 'ไม่สามารถอนุมัติได้ การจองนี้ยังไม่ได้ชำระเงิน']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }


    $conn->autocommit(false);

    try {
        // อัปเดตสถานะการจองเป็น "อนุมัติแล้ว"
        $sql_update_booking = "UPDATE Bookings SET booking_status = 'อนุมัติแล้ว' WHERE booking_id = ?";
        $stmt_update_booking = $conn->prepare($sql_update_booking);
        $stmt_update_booking->bind_param("i", $id);
        $stmt_update_booking->execute();


        $stmt_update_booking->close();

        // อัปเดตสถานะบูธเป็น "จองแล้ว"
        $sql_update_booth = "UPDATE Booths SET booth_status = 'จองแล้ว' WHERE booth_id = ?";
        $stmt_update_booth = $conn->prepare($sql_update_booth);
        $stmt_update_booth->bind_param("i", $booth_id);
        $stmt_update_booth->execute();


        $stmt_update_booth->close();
        $conn->commit();


        $response->getBody()->write(json_encode(['success' => 'การจองได้รับการอนุมัติแล้ว']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } catch (Exception $e) {

        // ถ้ามีข้อผิดพลาด ให้ rollback
        $conn->rollback();
        $response->getBody()->write(json_encode(['error' => 'เกิดข้อผิดพลาดในการอนุมัติการจอง']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    } finally {

        $conn->autocommit(true);
    }
});

// API สำหรับดูรายงานรายชื่อสมาชิก
$app->get('/admin/members', function (Request $request, Response $response) {
    $conn = $GLOBALS['connect'];

    // SQL คำสั่งสำหรับดึงข้อมูลสมาชิกจากตาราง user
    $sql = "SELECT first_name, last_name, phone, email FROM Users";


    $stmt = $conn->prepare($sql);
    if ($stmt === false) {
        $response->getBody()->write(json_encode(['error' => 'ไม่สามารถดึงข้อมูลได้']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
    $stmt->execute();

    $result = $stmt->get_result();
    // เก็บข้อมูลทั้งหมดลงใน array
    $members = [];
    while ($row = $result->fetch_assoc()) {
        $members[] = [
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'phone' => $row['phone'],
            'email' => $row['email']
        ];
    }


    $stmt->close();


    if (empty($members)) {
        $response->getBody()->write(json_encode(['message' => 'ไม่พบข้อมูลสมาชิก']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }


    $response->getBody()->write(json_encode($members));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});

//  รายงานผู้ที่ยังไม่ชำระเงิน สถานะเป็น "จอง"
$app->get('/admin/unpaid', function (Request $request, Response $response) {
    $conn = $GLOBALS['connect'];

    // SQL ดึงข้อมูลผู้ที่ยังไม่ชำระเงิน
    $sql = "
        SELECT 
            u.first_name,
            u.last_name,
            u.phone,
            b.booth_name,
            z.zone_name
        FROM Bookings AS bk
        JOIN Users AS u ON bk.user_id = u.user_id
        JOIN Booths AS b ON bk.booth_id = b.booth_id
        JOIN Zones AS z ON b.zone_id = z.zone_id
        WHERE bk.booking_status = 'จองแล้ว'
        ";


    $stmt = $conn->prepare($sql);

    // ตรวจสอบการเตรียม statement 
    if ($stmt === false) {
        $response->getBody()->write(json_encode(['error' => 'ไม่สามารถดึงข้อมูลได้']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }
    $stmt->execute();
    $result = $stmt->get_result();

    // เก็บข้อมูลทั้งหมดลงใน array
    $unpaidReservations = [];
    while ($row = $result->fetch_assoc()) {
        $unpaidReservations[] = [
            'first_name' => $row['first_name'],
            'last_name' => $row['last_name'],
            'phone' => $row['phone'],
            'booth_name' => $row['booth_name'],
            'zone_name' => $row['zone_name']
        ];
    }


    $stmt->close();

    if (empty($unpaidReservations)) {
        $response->getBody()->write(json_encode(['message' => 'ไม่พบข้อมูลผู้ที่ยังไม่ชำระเงิน']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    $response->getBody()->write(json_encode($unpaidReservations));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});

$app->get('/admin/paid-bookings', function ($request, $response, $args) {

    $db = $GLOBALS['connect'];
    //SQL ดึงข้อมูลการจองที่มีสถานะ "ชำระเงินแล้ว"
    $sql = "
        SELECT 
            u.first_name,
            u.last_name,
            u.phone,
            b.booth_name,
            z.zone_name
        FROM Bookings AS bk
        JOIN Users AS u ON bk.user_id = u.user_id
        JOIN Booths AS b ON bk.booth_id = b.booth_id
        JOIN Zones AS z ON b.zone_id = z.zone_id
        WHERE bk.booking_status = 'ชำระเงินแล้ว'
    ";

    $stmt = $db->prepare($sql);
    $stmt->execute();
    $result = $stmt->get_result();
    $paidBookings = $result->fetch_all(MYSQLI_ASSOC);


    $response->getBody()->write(json_encode($paidBookings));
    return $response->withHeader('Content-Type', 'application/json');
});

$app->get('/admin/pending-bookings', function ($request, $response, $args) {

    $db = $GLOBALS['connect'];

    // ดึงข้อมูลการจองที่มีสถานะ "อยู่ระหว่างตรวจสอบ"
    $sql = "
        SELECT 
            u.first_name,
            u.last_name,
            u.phone,
            b.booth_name,
            z.zone_name
        FROM Bookings AS bk
        JOIN Users AS u ON bk.user_id = u.user_id
        JOIN Booths AS b ON bk.booth_id = b.booth_id
        JOIN Zones AS z ON b.zone_id = z.zone_id
        WHERE b.booth_status = 'อยู่ระหว่างตรวจสอบ'
    ";

    // ดำเนินการคำสั่ง SQL
    $stmt = $db->prepare($sql);
    $stmt->execute();

    // ดึงข้อมูลทั้งหมด
    $result = $stmt->get_result();
    $paidBookings = $result->fetch_all(MYSQLI_ASSOC);

    // ส่งข้อมูลกลับในรูปแบบ JSON
    $response->getBody()->write(json_encode($paidBookings));
    return $response->withHeader('Content-Type', 'application/json');
});


$app->get('/admin/all-bookings', function ($request, $response, $args) {

    $db = $GLOBALS['connect'];

    // SQL ดึงข้อมูลการจองบูธทั้งหมด
    $sql = "
        SELECT 
            u.first_name,
            u.last_name,
            z.zone_name,
            b.booth_price,
            b.booth_name,
            bk.booking_status
        FROM Bookings AS bk
        JOIN Users AS u ON bk.user_id = u.user_id
        JOIN Booths AS b ON bk.booth_id = b.booth_id
        JOIN Zones AS z ON b.zone_id = z.zone_id
    ";

    $result = mysqli_query($db, $sql);


    if (mysqli_num_rows($result) > 0) {
        $allBookings = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $allBookings[] = $row;
        }


        $response->getBody()->write(json_encode($allBookings));
        return $response->withHeader('Content-Type', 'application/json');
    } else {

        $response->getBody()->write(json_encode([]));
        return $response->withHeader('Content-Type', 'application/json');
    }
});

//-----------------------------------GUSEST------------------------------------------------


//register 

$app->post('/register', function (Request $request, Response $response) {
    $db = $GLOBALS['connect'];
    $data = json_decode($request->getBody(), true); // แก้ไขการดึงข้อมูล

    // ตรวจสอบว่ามีข้อมูลที่จำเป็นอยู่ใน $data
    if ($data === null || !isset(
        $data['prefix'],
        $data['first_name'],
        $data['last_name'],
        $data['phone'],
        $data['email'],
        $data['password']
    )) {
        $response->getBody()->write(json_encode(['message' => 'ข้อมูลที่ส่งมาผิดพลาด']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    // ดึงค่าจาก $data
    $prefix = $data['prefix'];
    $firstName = $data['first_name'];
    $lastName = $data['last_name'];
    $phone = $data['phone'];
    $email = $data['email'];
    $password = $data['password'];

    // ตรวจสอบว่ามีอีเมลนี้ในระบบหรือไม่
    $sqlCheck = "SELECT COUNT(*) FROM Users WHERE email = ?";
    $stmtCheck = mysqli_prepare($db, $sqlCheck);
    mysqli_stmt_bind_param($stmtCheck, 's', $email);
    mysqli_stmt_execute($stmtCheck);
    mysqli_stmt_bind_result($stmtCheck, $emailCount);
    mysqli_stmt_fetch($stmtCheck);
    mysqli_stmt_close($stmtCheck); // ปิด statement หลังการใช้งาน

    if ($emailCount > 0) {
        $response->getBody()->write(json_encode(['message' => 'อีเมลนี้ถูกใช้ไปแล้ว']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    // แฮชรหัสผ่าน
    if (empty($password)) {
        $response->getBody()->write(json_encode(['message' => 'รหัสผ่านไม่ถูกต้อง']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
    }

    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

    // SQL เพิ่มข้อมูลผู้ใช้ใหม่
    $sql = "INSERT INTO Users (first_name, last_name, phone, email, password, prefix) VALUES (?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 'ssssss', $firstName, $lastName, $phone, $email, $hashedPassword, $prefix);

    if (mysqli_stmt_execute($stmt)) {
        $response->getBody()->write(json_encode(['message' => 'สมัครสมาชิกสำเร็จ']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(201);
    } else {
        $response->getBody()->write(json_encode(['message' => 'เกิดข้อผิดพลาดในการสมัครสมาชิก']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }

    mysqli_stmt_close($stmt); // ปิด statement หลังการใช้งาน
});


//Get Zones from gusest
$app->get('/gusest/zones', function (Request $request, Response $response) {
    $db = $GLOBALS['connect'];

    // SQL Query
    $sql = "SELECT 
                z.zone_id AS zone_id, 
                z.zone_name AS zone_name, 
                z.zone_info AS zone_info, 
                COUNT(b.booth_id) AS booth_count 
            FROM 
                Zones z 
            LEFT JOIN 
                Booths b ON z.zone_id = b.zone_id 
            GROUP BY 
                z.zone_id, z.zone_name, z.zone_info";

    $result = mysqli_query($db, $sql);

    if (!$result) {
        $response->getBody()->write(json_encode(['message' => 'เกิดข้อผิดพลาดในการดึงข้อมูล']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(500);
    }

    $zones = [];
    while ($row = mysqli_fetch_assoc($result)) {
        $zones[] = $row;
    }

    $response->getBody()->write(json_encode($zones));
    return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
});

$app->get('/gusest/booths/{id}', function (Request $request, Response $response, $args) {
    $db = $GLOBALS['connect'];

    // Log Request
    error_log("Request received for booth ID: " . $args['id']);

    // รับ ID ของบูธจากพารามิเตอร์
    $boothId = $args['id'];

    // SQL คิวรีเพื่อดึงข้อมูลบูธ
    $sql = "SELECT booth_id, booth_name, booth_size, booth_status, booth_price, booth_image FROM Booths WHERE booth_id = ?";
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 'i', $boothId);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $id, $name, $size, $status, $price, $image);


    if (mysqli_stmt_fetch($stmt)) {
        $boothDetails = [
            'booth_id' => $id,
            'booth_name' => $name,
            'booth_size' => $size,
            'booth_status' => $status,
            'booth_price' => $price,
            'booth_image' => $image,
        ];

        // Log Response
        error_log("Response sent: " . json_encode($boothDetails));

        $response->getBody()->write(json_encode($boothDetails));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
    } else {
        $response->getBody()->write(json_encode(['message' => 'ไม่พบข้อมูลบูธที่ระบุ']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }
});



//--------------------------------------------MEMBER----------------------------------------------------


// เข้าสู่ระบบสมาชิก
$app->post('/login', function (Request $request, Response $response) {
    $db = $GLOBALS['connect'];

    // รับข้อมูลจาก form
    $data = json_decode($request->getBody()->getContents(), true);
    $email = $data['email'] ?? null;
    $password = $data['password'] ?? null;

    if (!$email || !$password) {
        $response->getBody()->write(json_encode(['message' => 'กรุณากรอกอีเมลและรหัสผ่าน']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }

    // คิวรีเพื่อดึงข้อมูลสมาชิก
    $sql = "SELECT user_id, first_name, last_name, phone, email, password, prefix FROM Users WHERE email = ?";
    $stmt = mysqli_prepare($db, $sql);
    mysqli_stmt_bind_param($stmt, 's', $email);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $userId, $firstName, $lastName, $phone, $email, $hashedPassword, $prefix);

    // ตรวจสอบผลลัพธ์
    if (mysqli_stmt_fetch($stmt)) {
        // ตรวจสอบรหัสผ่าน
        if (password_verify($password, $hashedPassword)) {
            // สร้างข้อมูล JSON เพื่อตอบกลับ
            $userDetails = [
                'user_id' => $userId,
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
                'email' => $email,
                'prefix' => $prefix,
            ];


            $response->getBody()->write(json_encode($userDetails));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(200);
        } else {
            $response->getBody()->write(json_encode(['message' => 'รหัสผ่านไม่ถูกต้อง']));
            return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
        }
    } else {
        $response->getBody()->write(json_encode(['message' => 'ไม่พบสมาชิกที่มีอีเมลนี้']));
        return $response->withHeader('Content-Type', 'application/json')->withStatus(404);
    }
});



$app->run();

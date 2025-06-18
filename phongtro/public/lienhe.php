<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Liên hệ với chúng tôi</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #f4f4f4;
            margin: 0;
            padding: 0;
        }

        .container {
            width: 500px;
            margin: 50px auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 0 10px #ccc;
        }

        h2 {
            text-align: center;
            color: #333;
        }

        label {
            font-weight: bold;
            display: block;
            margin-top: 15px;
        }

        input[type="text"],
        input[type="email"],
        textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
        }

        textarea {
            resize: vertical;
        }

        input[type="submit"] {
            margin-top: 20px;
            width: 100%;
            background: #28a745;
            color: white;
            padding: 12px;
            border: none;
            border-radius: 5px;
            font-size: 16px;
            cursor: pointer;
        }

        input[type="submit"]:hover {
            background: #218838;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Liên hệ với quản trị viên</h2>
        <form action="lienhe_xuly.php" method="post">
            <label>Họ tên:</label>
            <input type="text" name="ho_ten" required>

            <label>Email:</label>
            <input type="email" name="email" required>

            <label>Tiêu đề:</label>
            <input type="text" name="tieu_de" required>

            <label>Nội dung:</label>
            <textarea name="noi_dung" rows="5" required></textarea>

            <input type="submit" value="Gửi liên hệ">
        </form>
    </div>
</body>
</html>

<h2 style="text-align:center;">User List</h2>

<table border="1" width="100%" cellspacing="0" cellpadding="5">
    <thead>
        <tr>
            <th>ID</th>
            <th>Photo</th>
            <th>Name</th>
            <th>Email</th>
            <th>Phone</th>
            <th>Age</th>
            <th>Gender</th>
            <th>Address</th>
            <th>Salary</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($allUsers as $u): ?>
            <tr>
                <td><?= htmlspecialchars($u['id']) ?></td>
                <td>
                    <?php
                        $imagePath = "uploads/" . $u['profile_photo'];
                        if (file_exists($imagePath)) {
                            echo '<img src="' . resizeImageBase64($imagePath) . '" style="width:30px; height:30px; border-radius:50%; object-fit:cover;">';
                        } else {
                            echo 'N/A';
                        }
                    ?>
                </td>
                <td><?= htmlspecialchars($u['name']) ?></td>
                <td><?= htmlspecialchars($u['email']) ?></td>
                <td><?= htmlspecialchars($u['phone']) ?></td>
                <td><?= htmlspecialchars($u['age']) ?></td>
                <td><?= htmlspecialchars($u['gender']) ?></td>
                <td><?= htmlspecialchars($u['address']) ?></td>
                <td><?= htmlspecialchars($u['salary']) ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

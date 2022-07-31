# 🚀 QueryLite - Lightweight SQL Query Builder

QueryLite is a **fast**, **lightweight**, and **easy-to-use** database query builder that simplifies SQL interactions using native **PDO**. It provides an intuitive **model-based API** while ensuring raw SQL performance.

---

## 📌 Example Usage

### **1️⃣ Define a Model**
Extend `QueryLite` to define your own database model.

```php
class UserModel extends QueryLite
{
    const TABLE = 'users';

    protected function create($name, $email) {
        $this->insert(['name' => $name, 'email' => $email]);
    }
}
```

### **2️⃣ Establish a Database Connection**
Before using QueryLite, set up a **PDO connection**:

```php
$connection = new PDO(
    "mysql:host=127.0.0.1;dbname=your_database;charset=utf8mb4",
    "your_user",
    "your_password",
    [
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]
);
```

### **3️⃣ Query the Database**

🔹 **Select Data**
```php
$userModel = new UserModel($connection);

$users = $userModel->select(['id', 'name'])
    ->where('age', '>', 18)
    ->orderBy('name ASC')
    ->limit(100)
    ->getAllRows();

```

🔹 **Insert Data**
```php
$userModel->insert([
    'name' => 'John Doe',
    'email' => 'john@example.com'
]);
```

🔹 **Update Data**
```php
$userModel->update(['email' => 'new@example.com'])
    ->whereEqual('id', 1);
```

🔹 **Delete Data**
```php
$userModel->delete()
    ->whereEqual('id', 5);
```

---

## 🛠 QueryLite API Methods

| Method | Description | Example |
|--------|------------|---------|
| `select($columns)` | Select specific columns from a table | `$userModel->select(['id', 'name']);` |
| `where($column, $operator, $value)` | Add a WHERE condition | `$userModel->where('status', '=', 'active');` |
| `orderBy($column, $direction)` | Add sorting to queries | `$userModel->orderBy('id', 'DESC');` |
| `limit($count)` | Limit query results | `$userModel->limit(50);` |
| `insert($data)` | Insert new records | `$userModel->insert(['name' => 'Jane']);` |
| `update($data)` | Update records | `$userModel->update(['email' => 'test@example.com']);` |
| `delete()` | Delete records | `$userModel->delete()->whereEqual('id', 1);` |
| `getAllRows()` | Fetch all results | `$userModel->getAllRows();` |


## 🛠 Running Tests

To ensure QueryLite functions correctly, run:

```sh
# Run all tests using Composer
composer test
```

---

## 🌟 Features

✅ **Minimal & Fast** – Built on native PDO for raw SQL execution.  
✅ **Chainable Query Methods** – Clean and readable query building.  
✅ **Supports MySQL & SQLite** – Works seamlessly with popular databases.  
✅ **No Dependencies** – Fully self-contained, no external libraries needed.  
✅ **Customizable & Extendable** – Easily define models and extend functionality.  

---

## 👨‍💻 Contribution & Support

Feel free to **contribute** or provide feedback to improve QueryLite. If you encounter any issues, submit a **bug report** or suggest **new features**.

📩 **Contact:** Open an issue on GitHub or email **sukhrobnuralievv@gmail.com**  



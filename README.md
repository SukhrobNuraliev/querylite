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

<?php

namespace SouthCoast\SouthWest\Connector;

use \Exception;

class MySql
{
    private $link = null;
    private static $info = [
        'last_query' => null,
        'num_rows' => null,
        'insert_id' => null,
    ];

    private static $connection_info = [];

    private static $where;
    private static $limit;
    private static $order;

    protected $query = '';

    public function __construct(...$auth_details)
    {
        self::$connection_info = ['host' => $auth_details[0], 'user' => $auth_details[1], 'pass' => $auth_details[2], 'db' => $auth_details[3]];
        $this->connect(...$auth_details);
    }

    public function __destruct()
    {
        if (is_resource($this->link)) {
            mysqli_close($this->link);
        }
    }

    /**
     * Setter method
     */

    private static function set($field, $value)
    {
        self::$info[$field] = $value;
    }

    public function setQuery(string $query): bool
    {
        self::set('last_query', $query);
        $this->query = $query;

        return true;
    }

    public function prepared(string $query, array $values, bool $returnBool = false)
    {
        $query = $this->link->prepare($query);

        $types = '';

        foreach ($values as $index => $value) {
            switch (gettype($value)) {
                case 'string':
                    $types .= 's';
                    break;

                case 'integer':
                    $types .= 'i';
                    break;

                case 'double' || 'float':
                    $types .= 'd';
                    break;

                case 'array':
                    $values[$index] = json_encode($value);
                    $types .= 's';
                    break;

                default:
                    $values[$index] = (string) $value;
                    $types .= 's';
                    break;
            }
        }

        array_unshift($values, $types);

        $prepared = $query->bind_param(...$values);

        $result = $prepared->execute();

        if (!$result) {
            return $this->SQLError();
        }

        return $this->result2Array($result);

        $prepared->close();
    }

    /**
     * Getter methods
     */

    public function last_query(string $query = null)
    {
        if (!is_null($query)) {
            return self::set('last_query', $query);
        }

        return self::$info['last_query'];
    }

    public function num_rows()
    {
        return self::$info['num_rows'];
    }

    public function insert_id()
    {
        return self::$info['insert_id'];
    }

    /**
     * Create or return a connection to the MySQL server.
     */

    protected function connect()
    {
        if (!isset($this->link) || empty($this->link)) {
            if (($link = mysqli_connect(self::$connection_info['host'], self::$connection_info['user'], self::$connection_info['pass'], self::$connection_info['db']))) {
                $this->link = $link;
            } else {
                throw new Exception('Could not connect to MySQL database.');
            }
        }
        return $this->link;
    }

    /**
     * MySQL Where methods
     */

    private function __where($info, $type = 'AND')
    {
        $where = self::$where;
        foreach ($info as $row => $value) {
            if (empty($where)) {
                $where = sprintf("WHERE `%s`='%s'", $row, $this->link->real_escape_string($value));
            } else {
                $where .= sprintf(" %s `%s`='%s'", $type, $row, $this->link->real_escape_string($value));
            }
        }
        self::$where = $where;
    }

    public function where($field, $equal = null)
    {
        if (is_array($field)) {
            self::__where($field);
        } else {
            self::__where([$field => $equal]);
        }
        return $this;
    }

    public function and_where($field, $equal = null)
    {
        return $this->where($field, $equal);
    }

    public function or_where($field, $equal = null)
    {
        if (is_array($field)) {
            self::__where($field, 'OR');
        } else {
            self::__where(array($field => $equal), 'OR');
        }
        return $this;
    }

    /**
     * MySQL limit method
     */

    public function limit($limit)
    {
        self::$limit = 'LIMIT ' . $limit;
        return $this;
    }

    /**
     * MySQL Order By method
     */

    public function order_by($by, $order_type = 'DESC')
    {
        $order = self::$order;
        if (is_array($by)) {
            foreach ($by as $field => $type) {
                if (is_int($field) && !preg_match('/(DESC|desc|ASC|asc)/', $type)) {
                    $field = $type;
                    $type = $order_type;
                }
                if (empty($order)) {
                    $order = sprintf("ORDER BY `%s` %s", $field, $type);
                } else {
                    $order .= sprintf(", `%s` %s", $field, $type);
                }
            }
        } else {
            if (empty($order)) {
                $order = sprintf("ORDER BY `%s` %s", $by, $order_type);
            } else {
                $order .= sprintf(", `%s` %s", $by, $order_type);
            }
        }
        self::$order = $order;
        return $this;
    }

    /**
     * MySQL query helper
     */

    private static function extra()
    {
        $extra = '';

        if (!empty(self::$where)) {
            $extra .= ' ' . self::$where;
        }

        if (!empty(self::$order)) {
            $extra .= ' ' . self::$order;
        }

        if (!empty(self::$limit)) {
            $extra .= ' ' . self::$limit;
        }

        // cleanup
        self::$where = null;
        self::$order = null;
        self::$limit = null;

        return $extra;
    }

    /**
     * MySQL Query methods
     */

    public function query(string $query = null, $return = false)
    {
        if (!is_null($query)) {
            $this->setQuery($query);
        }

        $result = $this->link->query($this->query);

        if (is_resource($result)) {
            self::set('num_rows', $result->num_rows);
        }

        if ($return) {
            if (preg_match('/LIMIT 1/', $query)) {
                $data = $result->fetch_assoc();
                $result->free_result();
                return $data;
            } else {
                $data = [];
                while ($row = $result->fetch_assoc()) {
                    $data[] = $row;
                }
                $result->free_result();
                return $data;
            }
        }

        return true;
    }

    public function get($table, $select = '*')
    {
        // $link = self::connection();
        if (is_array($select)) {
            $cols = '';
            foreach ($select as $col) {
                $cols .= "`{$col}`,";
            }
            $select = substr($cols, 0, -1);
        }

        $sql = sprintf("SELECT %s FROM %s%s", $select, $table, self::extra());

        $this->last_query($sql);

        if (!($result = $this->link->query($sql))) {
            return $this->SQLError();
        } elseif ($result) {
            self::set('num_rows', $result->num_rows);

            if ($result->num_rows === 0) {
                $data = [];
            } elseif (preg_match('/LIMIT 1/', $sql)) {
                $data = $result->fetch_assoc();
                $result->free_result();
            } else {
                $data = $this->result2Array($result);
            }
        } else {
            $data = false;
        }

        return $data;
    }

    public function insert($table, $data)
    {
        // $link = self::connection();

        $fields = '';
        $values = '';

        foreach ($data as $col => $value) {
            $fields .= sprintf("`%s`,", $col);
            $values .= sprintf("'%s',", $this->link->real_escape_string($value)($value));
        }

        $fields = substr($fields, 0, -1);
        $values = substr($values, 0, -1);

        $sql = sprintf("INSERT INTO %s (%s) VALUES (%s)", $table, $fields, $values);

        $this->last_query($sql);

        if (!$this->link->query($sql)) {
            return $this->SQLError();
        } else {
            self::set('insert_id', $this->link->insert_id);
            return true;
        }
    }

    public function update($table, $info)
    {
        if (empty(self::$where)) {
            throw new Exception("'Where' is not set. Can't update whole table.");
        }

        $update = '';

        foreach ($info as $col => $value) {
            $update .= sprintf("`%s`='%s', ", $col, $this->link->real_escape_string($value));
        }

        $update = substr($update, 0, -2);

        $sql = sprintf("UPDATE %s SET %s%s", $table, $update, self::extra());
        $this->last_query($sql);

        if (!$this->link->query($sql)) {
            return $this->SQLError();
        } else {
            return true;
        }
    }

    public function delete($table)
    {
        if (empty(self::$where)) {
            throw new Exception("'Where' is not set. Can't delete whole table.");
        }

        // $link = self::connection();
        $sql = sprintf("DELETE FROM %s%s", $table, self::extra());

        $this->last_query($sql);

        if (!$this->link->query($sql)) {
            return $this->SQLError();
        } else {
            return true;
        }
    }

    public function columnNames(string $table): array
    {
        $query = sprintf('SHOW COLUMNS FROM %s', $this->link->real_escape_string($table));
        $this->last_query($query);

        if (!($result = $this->link->query($query))) {
            return $this->SQLError();
        }

        $response = [];

        foreach ($this->result2Array($result) as $field) {
            $response[] = $field['Field'];
        }

        return $response;
    }

    public function columns(string $table)
    {
        $query = sprintf('SHOW COLUMNS FROM %s', $this->link->real_escape_string($table));
        $this->last_query($query);

        if (!($result = $this->link->query($query))) {
            return $this->SQLError();
        }

        return $this->result2Array($result);
    }

    public function result2Array(\mysqli_result $result)
    {
        $data = [];
        while ($row = $result->fetch_assoc()) {
            $data[] = $row;
        }
        $result->free_result();
        return $data;
    }

    protected function SQLError()
    {
        /* TODO: CREATE AN SQL ERROR EXCEPTION OBJECT */
        throw new \Exception('Error executing MySQL query: "' . $this->last_query() . '". MySQL error ' . $this->link->errno . ': ' . $this->link->error);
    }
}

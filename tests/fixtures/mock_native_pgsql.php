<?php

namespace CCMBenchmark\Ting\Driver\Pgsql;

class PGMock
{
    private static $overridenFunctions = [];

    public static function override($function, $return)
    {
        if (!function_exists("\CCMBenchmark\Ting\Driver\Pgsql\\" . $function)) {
            throw new \UnexpectedValueException("Trying to override a function but not declared in this file: " . $function);
        }
        self::$overridenFunctions[$function] = $return;
    }

    public static function cancelOverride($function)
    {
        unset(self::$overridenFunctions[$function]);
    }

    public static function isOverriden($function)
    {
        return array_key_exists($function, self::$overridenFunctions);
    }

    public static function call(string $function, array $args)
    {
        if (!self::isOverriden($function)) {
            return call_user_func('\\' . $function, ...$args);
        }
        if (is_callable(self::$overridenFunctions[$function])) {
            return self::$overridenFunctions[$function](...$args);
        }

        return self::$overridenFunctions[$function];
    }
}
function pg_last_error()
{
    return PGMock::call('pg_last_error', func_get_args());
}
function pg_execute()
{
    return PGMock::call('pg_execute', func_get_args());
}
function pg_query()
{
    return PGMock::call('pg_query', func_get_args());
}
function pg_set_client_encoding()
{
    return PGMock::call('pg_set_client_encoding', func_get_args());
}

function pg_connect()
{
    return PGMock::call('pg_connect', func_get_args());
}

function pg_close()
{
    return PGMock::call('pg_close', func_get_args());
}

function pg_ping()
{
    return PGMock::call('pg_ping', func_get_args());
}

function pg_result_status()
{
    return PGMock::call('pg_result_status', func_get_args());
}

function pg_result_seek()
{
    return PGMock::call('pg_result_seek', func_get_args());
}

function pg_field_table()
{
    return PGMock::call('pg_field_table', func_get_args());
}

function pg_fetch_row()
{
    return PGMock::call('pg_fetch_row', func_get_args());
}

function pg_fetch_assoc()
{
    return PGMock::call('pg_fetch_assoc', func_get_args());
}

function pg_prepare()
{
    return PGMock::call('pg_prepare', func_get_args());
}

function pg_num_rows()
{
    return PGMock::call('pg_num_rows', func_get_args());
}
function pg_query_params()
{
    return PGMock::call('pg_query_params', func_get_args());
}
function pg_fetch_array()
{
    return PGMock::call('pg_fetch_array', func_get_args());
}
function pg_num_fields()
{
    return PGMock::call('pg_num_fields', func_get_args());
}
function pg_errormessage()
{
    return PGMock::call('pg_errormessage', func_get_args());
}
function pg_affected_rows()
{
    return PGMock::call('pg_affected_rows', func_get_args());
}
function pg_field_name()
{
    return PGMock::call('pg_field_name', func_get_args());
}

param(
    [string]$MdbPath = 'E:\BENGKEL 2.0\FITMOTOR GABUNG.MDB',
    [string]$MySqlExe = 'C:\laragon\bin\mysql\mysql-8.4.3-winx64\bin\mysql.exe',
    [string]$MySqlHost = 'localhost',
    [string]$MySqlUser = 'fitmotor_LOGIN',
    [string]$MySqlPassword = 'Sayalupa12',
    [string]$MySqlDatabase = 'fitmotor_gabung',
    [string]$ProjectRoot = (Resolve-Path '.').Path,
    [switch]$ResolveAccessQueryFields
)

$ErrorActionPreference = 'Stop'

function Ensure-Dir([string]$Path) {
    if (-not (Test-Path -LiteralPath $Path)) {
        New-Item -ItemType Directory -Path $Path | Out-Null
    }
}

function Sql-Ident([string]$Name) {
    return '`' + ($Name -replace '`', '``') + '`'
}

function Md-Escape([object]$Value) {
    if ($null -eq $Value) { return '' }
    return ([string]$Value) -replace '\|', '\|' -replace "`r?`n", '<br>'
}

function Access-TypeName([int]$Type) {
    $map = @{
        1='Boolean'; 2='Byte'; 3='Integer'; 4='Long'; 5='Currency'; 6='Single'; 7='Double';
        8='DateTime'; 9='Binary'; 10='Text'; 11='LongBinary'; 12='Memo'; 15='GUID';
        16='BigInt'; 17='VarBinary'; 18='Char'; 19='Numeric'; 20='Decimal'; 21='Float';
        22='Time'; 23='TimeStamp'; 101='Attachment'; 102='ComplexByte'; 103='ComplexInteger';
        104='ComplexLong'; 105='ComplexSingle'; 106='ComplexDouble'; 107='ComplexGUID';
        108='ComplexDecimal'; 109='ComplexText'
    }
    if ($map.ContainsKey($Type)) { return $map[$Type] }
    return "DAOType$Type"
}

function Access-ToMySqlType([object]$Field) {
    $type = [int]$Field.Type
    $size = 0
    try { $size = [int]$Field.Size } catch {}
    switch ($type) {
        1 { return 'TINYINT(1)' }
        2 { return 'TINYINT UNSIGNED' }
        3 { return 'SMALLINT' }
        4 { return 'INT' }
        5 { return 'DECIMAL(19,4)' }
        6 { return 'FLOAT' }
        7 { return 'DOUBLE' }
        8 { return 'DATETIME' }
        10 {
            if ($size -le 0) { $size = 255 }
            if ($size -gt 16383) { return 'TEXT' }
            return "VARCHAR($size)"
        }
        11 { return 'LONGBLOB' }
        12 { return 'LONGTEXT' }
        15 { return 'CHAR(36)' }
        16 { return 'BIGINT' }
        17 { return 'VARBINARY(255)' }
        18 { return 'CHAR(1)' }
        19 { return 'DECIMAL(19,4)' }
        20 { return 'DECIMAL(19,4)' }
        21 { return 'DOUBLE' }
        22 { return 'TIME' }
        23 { return 'DATETIME' }
        default { return 'TEXT' }
    }
}

function Normalize-Name([string]$Name) {
    if ($null -eq $Name) { return '' }
    $n = $Name.ToLowerInvariant()
    $n = $n -replace '[^a-z0-9]', ''
    $n = $n -replace '^kd', 'kode'
    $n = $n -replace 'tgl', 'tanggal'
    $n = $n -replace '^nm', 'nama'
    $n = $n -replace 'no', 'nomor'
    return $n
}

function Levenshtein([string]$a, [string]$b) {
    if ($a -eq $b) { return 0 }
    if ([string]::IsNullOrEmpty($a)) { return $b.Length }
    if ([string]::IsNullOrEmpty($b)) { return $a.Length }
    $d = New-Object 'int[,]' ($a.Length + 1), ($b.Length + 1)
    for ($i = 0; $i -le $a.Length; $i++) { $d[$i,0] = $i }
    for ($j = 0; $j -le $b.Length; $j++) { $d[0,$j] = $j }
    for ($i = 1; $i -le $a.Length; $i++) {
        for ($j = 1; $j -le $b.Length; $j++) {
            $cost = if ($a[$i-1] -eq $b[$j-1]) { 0 } else { 1 }
            $deleteCost = $d[($i - 1),$j] + 1
            $insertCost = $d[$i,($j - 1)] + 1
            $replaceCost = $d[($i - 1),($j - 1)] + $cost
            $d[$i,$j] = [Math]::Min([Math]::Min($deleteCost, $insertCost), $replaceCost)
        }
    }
    return $d[$a.Length,$b.Length]
}

function Invoke-MySqlTsv([string]$Sql) {
    $psi = New-Object System.Diagnostics.ProcessStartInfo
    $psi.FileName = $MySqlExe
    $psi.UseShellExecute = $false
    $psi.RedirectStandardOutput = $true
    $psi.RedirectStandardError = $true
    $psi.StandardOutputEncoding = [System.Text.Encoding]::UTF8
    $psi.StandardErrorEncoding = [System.Text.Encoding]::UTF8
    $psi.Arguments = @(
        "-h $MySqlHost"
        "-u $MySqlUser"
        "-p$MySqlPassword"
        '--default-character-set=latin1'
        '--batch'
        '--raw'
        '--skip-column-names'
        ('-e "' + ($Sql -replace '"', '\"') + '"')
        $MySqlDatabase
    ) -join ' '
    $proc = [System.Diagnostics.Process]::Start($psi)
    $stdout = $proc.StandardOutput.ReadToEnd()
    $stderr = $proc.StandardError.ReadToEnd()
    $proc.WaitForExit()
    if ($proc.ExitCode -ne 0) {
        throw "mysql exited with code $($proc.ExitCode) while running: $Sql`n$stderr"
    }
    $output = @($stdout -split "`r?`n" | Where-Object { $_ -and $_.Trim() -ne '' })
    return $output
}

function Convert-AccessSqlToMySql([string]$Sql) {
    if ([string]::IsNullOrWhiteSpace($Sql)) { return '' }
    $s = $Sql.Trim()
    $s = $s -replace ';+\s*$', ''
    $s = $s -replace '\[([^\]]+)\]', '`$1`'
    $s = $s -replace '\bNz\s*\(', 'IFNULL('
    $s = $s -replace '\bDate\s*\(\s*\)', 'CURDATE()'
    $s = $s -replace '\bNow\s*\(\s*\)', 'NOW()'
    $s = $s -replace '\bYes\b', '1'
    $s = $s -replace '\bNo\b', '0'
    $s = $s -replace '\bTrue\b', '1'
    $s = $s -replace '\bFalse\b', '0'
    $s = $s -replace '\bIIf\s*\(', 'IF('
    $s = $s -replace '\bUCase\s*\(', 'UPPER('
    $s = $s -replace '\bLCase\s*\(', 'LOWER('
    $s = $s -replace '\bLen\s*\(', 'CHAR_LENGTH('
    $s = $s -replace '\bIsNull\s*\(', 'ISNULL('
    return $s
}

function Get-QueryOutputFields([object]$Db, [object]$QueryDef) {
    $fields = @()
    try {
        for ($i = 0; $i -lt $QueryDef.Fields.Count; $i++) {
            $f = $QueryDef.Fields.Item($i)
            $fieldSize = $null
            try { $fieldSize = [int]$f.Size } catch {}
            $fields += [pscustomobject]@{
                name = [string]$f.Name
                ordinal = $i + 1
                accessType = Access-TypeName ([int]$f.Type)
                size = $fieldSize
            }
        }
        return @{ fields = $fields; error = $null }
    } catch {
        return @{ fields = @(); error = $_.Exception.Message }
    }
}

function Split-TopLevelComma([string]$Text) {
    $parts = New-Object System.Collections.Generic.List[string]
    $sb = New-Object System.Text.StringBuilder
    $depth = 0
    $quote = $null
    for ($i = 0; $i -lt $Text.Length; $i++) {
        $ch = $Text[$i]
        if ($quote) {
            [void]$sb.Append($ch)
            if ($ch -eq $quote) { $quote = $null }
            continue
        }
        if ($ch -eq "'" -or $ch -eq '"') {
            $quote = $ch
            [void]$sb.Append($ch)
            continue
        }
        if ($ch -eq '(') { $depth++ }
        if ($ch -eq ')' -and $depth -gt 0) { $depth-- }
        if ($ch -eq ',' -and $depth -eq 0) {
            $parts.Add($sb.ToString().Trim())
            [void]$sb.Clear()
            continue
        }
        [void]$sb.Append($ch)
    }
    if ($sb.Length -gt 0) { $parts.Add($sb.ToString().Trim()) }
    return $parts
}

function Parse-AccessSelectFields([string]$Sql) {
    $fields = @()
    if ([string]::IsNullOrWhiteSpace($Sql)) { return $fields }
    $clean = $Sql -replace "`r?`n", ' '
    $m = [regex]::Match($clean, '(?is)\bSELECT\s+(?<select>.*?)\s+\bFROM\b')
    if (-not $m.Success) {
        $m = [regex]::Match($clean, '(?is)\bTRANSFORM\s+(?<select>.*?)\s+\bSELECT\b')
        if (-not $m.Success) { return $fields }
    }
    $selectList = $m.Groups['select'].Value.Trim()
    $selectList = $selectList -replace '^(?i)DISTINCT\s+', ''
    $selectList = $selectList -replace '^(?i)DISTINCTROW\s+', ''
    $ordinal = 1
    foreach ($part in Split-TopLevelComma $selectList) {
        $name = $null
        $aliasMatch = [regex]::Match($part, '(?is)\s+AS\s+(\[[^\]]+\]|`[^`]+`|"[^"]+"|[A-Za-z0-9_ ]+)\s*$')
        if ($aliasMatch.Success) {
            $name = $aliasMatch.Groups[1].Value.Trim()
        } elseif ($part -match '^\s*(\[[^\]]+\]|`[^`]+`|[A-Za-z0-9_]+)\.(\[[^\]]+\]|`[^`]+`|[A-Za-z0-9_]+)\s*$') {
            $name = $Matches[2]
        } elseif ($part -match '^\s*(\[[^\]]+\]|`[^`]+`|[A-Za-z0-9_]+)\s*$') {
            $name = $Matches[1]
        } else {
            $tail = [regex]::Match($part, '(?is)\s+(\[[^\]]+\]|`[^`]+`|"[^"]+"|[A-Za-z0-9_]+)\s*$')
            if ($tail.Success -and $part -notmatch '(?i)\)$') { $name = $tail.Groups[1].Value.Trim() }
        }
        if ($name) {
            $name = $name.Trim('[',']','`','"')
            $fields += [pscustomobject]@{ name=$name; ordinal=$ordinal; accessType='PARSED'; size=$null }
            $ordinal++
        }
    }
    return $fields
}

$auditDir = Join-Path $ProjectRoot 'docs\audit'
$migrationDir = Join-Path $ProjectRoot 'db\migrations'
Ensure-Dir $auditDir
Ensure-Dir $migrationDir

$dao = $null
foreach ($progId in @('DAO.DBEngine.120','DAO.DBEngine.36')) {
    try {
        $dao = New-Object -ComObject $progId
        break
    } catch {}
}
if (-not $dao) { throw 'DAO DBEngine is unavailable.' }

$db = $dao.OpenDatabase($MdbPath)

$accessTables = @()
for ($i = 0; $i -lt $db.TableDefs.Count; $i++) {
    $t = $db.TableDefs.Item($i)
    $fields = @()
    for ($j = 0; $j -lt $t.Fields.Count; $j++) {
        $f = $t.Fields.Item($j)
        $fieldSize = $null
        $fieldRequired = $null
        $fieldAllowZeroLength = $null
        $fieldDefaultValue = $null
        try { $fieldSize = [int]$f.Size } catch {}
        try { $fieldRequired = [bool]$f.Required } catch {}
        try { $fieldAllowZeroLength = [bool]$f.AllowZeroLength } catch {}
        try { $fieldDefaultValue = [string]$f.DefaultValue } catch {}
        $fields += [pscustomobject]@{
            name = [string]$f.Name
            ordinal = $j + 1
            daoType = [int]$f.Type
            accessType = Access-TypeName ([int]$f.Type)
            size = $fieldSize
            required = $fieldRequired
            allowZeroLength = $fieldAllowZeroLength
            defaultValue = $fieldDefaultValue
            mysqlTypeSuggestion = Access-ToMySqlType $f
        }
    }
    $primaryFields = @()
    $indexes = @()
    try {
        for ($ix = 0; $ix -lt $t.Indexes.Count; $ix++) {
            $idx = $t.Indexes.Item($ix)
            $idxFields = @()
            for ($k = 0; $k -lt $idx.Fields.Count; $k++) { $idxFields += [string]$idx.Fields.Item($k).Name }
            $indexes += [pscustomobject]@{ name=[string]$idx.Name; primary=[bool]$idx.Primary; unique=[bool]$idx.Unique; fields=$idxFields }
            if ($idx.Primary) { $primaryFields = $idxFields }
        }
    } catch {}
    $isSystem = ($t.Name -like 'MSys*' -or $t.Name -like '~*')
    $tableAttributes = $null
    try { $tableAttributes = [int]$t.Attributes } catch {}
    $accessTables += [pscustomobject]@{
        name = [string]$t.Name
        isSystem = $isSystem
        attributes = $tableAttributes
        fields = $fields
        primaryKey = $primaryFields
        indexes = $indexes
    }
}

$relations = @()
try {
    for ($i = 0; $i -lt $db.Relations.Count; $i++) {
        $r = $db.Relations.Item($i)
        $relFields = @()
        for ($j = 0; $j -lt $r.Fields.Count; $j++) {
            $rf = $r.Fields.Item($j)
            $relFields += [pscustomobject]@{ field=[string]$rf.Name; foreignField=[string]$rf.ForeignName }
        }
        $relations += [pscustomobject]@{
            name = [string]$r.Name
            table = [string]$r.Table
            foreignTable = [string]$r.ForeignTable
            fields = $relFields
        }
    }
} catch {}

$accessQueries = @()
for ($i = 0; $i -lt $db.QueryDefs.Count; $i++) {
    $q = $db.QueryDefs.Item($i)
    $sql = [string]$q.SQL
    if ($ResolveAccessQueryFields) {
        $output = Get-QueryOutputFields $db $q
    } else {
        $parsedFields = Parse-AccessSelectFields $sql
        $output = @{ fields = $parsedFields; error = if ($parsedFields.Count -eq 0) { 'Output fields parsed/unresolved; run with -ResolveAccessQueryFields for DAO resolution.' } else { 'Output fields parsed from SELECT list.' } }
    }
    $trim = $sql.TrimStart()
    $kind = if ($trim -match '^(?i)SELECT\b') { 'SELECT' } elseif ($trim -match '^(?i)TRANSFORM\b') { 'CROSSTAB' } elseif ($trim -match '^(?i)PARAMETERS\b') { 'PARAMETERS' } elseif ($trim -match '^(?i)UNION\b') { 'UNION' } else { 'OTHER' }
    $accessQueries += [pscustomobject]@{
        name = [string]$q.Name
        isInternal = ([string]$q.Name).StartsWith('~sq_')
        kind = $kind
        sql = $sql
        outputFields = $output.fields
        outputError = $output.error
    }
}
$db.Close()

$mysqlTablesRows = Invoke-MySqlTsv "SELECT TABLE_NAME, TABLE_TYPE, ENGINE FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME"
$mysqlColumnsRows = Invoke-MySqlTsv "SELECT TABLE_NAME, COLUMN_NAME, ORDINAL_POSITION, COLUMN_TYPE, DATA_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_KEY, EXTRA FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME, ORDINAL_POSITION"
$mysqlViewsRows = Invoke-MySqlTsv "SELECT TABLE_NAME, VIEW_DEFINITION FROM information_schema.VIEWS WHERE TABLE_SCHEMA = DATABASE() ORDER BY TABLE_NAME"

$mysqlTables = @{}
foreach ($line in $mysqlTablesRows) {
    if ([string]::IsNullOrWhiteSpace($line)) { continue }
    $p = $line -split "`t", 3
    $mysqlTables[$p[0]] = [pscustomobject]@{ name=$p[0]; type=$p[1]; engine=if($p.Length -gt 2){$p[2]}else{$null}; columns=@() }
}
foreach ($line in $mysqlColumnsRows) {
    if ([string]::IsNullOrWhiteSpace($line)) { continue }
    $p = $line -split "`t", 9
    if (-not $mysqlTables.ContainsKey($p[0])) {
        $mysqlTables[$p[0]] = [pscustomobject]@{ name=$p[0]; type='UNKNOWN'; engine=$null; columns=@() }
    }
    $mysqlTables[$p[0]].columns += [pscustomobject]@{
        name=$p[1]; ordinal=[int]$p[2]; columnType=$p[3]; dataType=$p[4]; nullable=$p[5]; default=$p[6]; key=$p[7]; extra=$p[8]
    }
}
$mysqlViews = @{}
foreach ($line in $mysqlViewsRows) {
    if ([string]::IsNullOrWhiteSpace($line)) { continue }
    $p = $line -split "`t", 2
    $mysqlViews[$p[0]] = [pscustomobject]@{ name=$p[0]; definition=if($p.Length -gt 1){$p[1]}else{''}; columns=($mysqlTables[$p[0]].columns) }
}

$userAccessTables = @($accessTables | Where-Object { -not $_.isSystem })
$accessTableByName = @{}
$userAccessTables | ForEach-Object { $accessTableByName[$_.name] = $_ }

$tableMap = @()
$fieldMap = @()
$priorityMismatches = @()
$tableFixSql = New-Object System.Collections.Generic.List[string]
$tableFixSql.Add('-- FITMOTOR Access -> MySQL table reconciliation script')
$tableFixSql.Add('-- Generated: ' + (Get-Date -Format 'yyyy-MM-dd HH:mm:ss zzz'))
$tableFixSql.Add('-- Review before execution. Access MDB is source of truth.')
$tableFixSql.Add('SET @OLD_FOREIGN_KEY_CHECKS=@@FOREIGN_KEY_CHECKS;')
$tableFixSql.Add('SET FOREIGN_KEY_CHECKS=0;')
$tableFixSql.Add('')

foreach ($at in $userAccessTables | Sort-Object Name) {
    $mt = if ($mysqlTables.ContainsKey($at.name)) { $mysqlTables[$at.name] } else { $null }
    $status = if ($mt) { 'OK' } else { 'MISSING_TABLE' }
    $note = if ($mt) { '' } else { 'Create table required in MySQL.' }
    $tableMap += [pscustomobject]@{ accessTable=$at.name; mysqlTable=if($mt){$mt.name}else{''}; status=$status; note=$note }
    if (-not $mt) {
        $cols = @()
        foreach ($f in $at.fields) {
            $nullSql = if ($f.required) { 'NOT NULL' } else { 'NULL' }
            $cols += '  ' + (Sql-Ident $f.name) + ' ' + $f.mysqlTypeSuggestion + ' ' + $nullSql
        }
        if ($at.primaryKey.Count -gt 0) {
            $cols += '  PRIMARY KEY (' + (($at.primaryKey | ForEach-Object { Sql-Ident $_ }) -join ', ') + ')'
        }
        $tableFixSql.Add('CREATE TABLE IF NOT EXISTS ' + (Sql-Ident $at.name) + ' (')
        $tableFixSql.Add(($cols -join ",`r`n"))
        $tableFixSql.Add(') ENGINE=InnoDB DEFAULT CHARSET=latin1;')
        $tableFixSql.Add('')
        $priorityMismatches += [pscustomobject]@{ object='TABLE'; name=$at.name; issue='Missing MySQL table'; impact='Crystal/query cannot bind this source'; action='Create table with Access field names' }
        continue
    }

    $mysqlColsByName = @{}
    $mt.columns | ForEach-Object { $mysqlColsByName[$_.name] = $_ }
    $unusedExtras = @($mt.columns)
    foreach ($af in $at.fields) {
        $mc = if ($mysqlColsByName.ContainsKey($af.name)) { $mysqlColsByName[$af.name] } else { $null }
        if ($mc) {
            $fieldMap += [pscustomobject]@{ table=$at.name; accessField=$af.name; mysqlField=$mc.name; status='OK'; action='' }
            $unusedExtras = @($unusedExtras | Where-Object { $_.name -ne $mc.name })
            continue
        }
        $best = $null
        $bestScore = 999
        $afn = Normalize-Name $af.name
        foreach ($extra in $unusedExtras) {
            $score = Levenshtein $afn (Normalize-Name $extra.name)
            if ($score -lt $bestScore) {
                $bestScore = $score
                $best = $extra
            }
        }
        if ($best -and $bestScore -le 2) {
            $fieldMap += [pscustomobject]@{ table=$at.name; accessField=$af.name; mysqlField=$best.name; status='LIKELY_RENAME'; action=("Rename {0} -> {1}" -f $best.name,$af.name) }
            $tableFixSql.Add('-- Likely rename in ' + $at.name + ': ' + $best.name + ' -> ' + $af.name)
            $tableFixSql.Add('ALTER TABLE ' + (Sql-Ident $at.name) + ' RENAME COLUMN ' + (Sql-Ident $best.name) + ' TO ' + (Sql-Ident $af.name) + ';')
            $tableFixSql.Add('')
            $priorityMismatches += [pscustomobject]@{ object='FIELD'; name=($at.name + '.' + $af.name); issue=('Likely renamed as ' + $best.name); impact='Crystal field binding may fail'; action='Rename MySQL column to Access field name' }
            $unusedExtras = @($unusedExtras | Where-Object { $_.name -ne $best.name })
        } else {
            $fieldMap += [pscustomobject]@{ table=$at.name; accessField=$af.name; mysqlField=''; status='MISSING_FIELD'; action='Add field' }
            $tableFixSql.Add('ALTER TABLE ' + (Sql-Ident $at.name) + ' ADD COLUMN ' + (Sql-Ident $af.name) + ' ' + $af.mysqlTypeSuggestion + ' NULL;')
            $tableFixSql.Add('')
            $priorityMismatches += [pscustomobject]@{ object='FIELD'; name=($at.name + '.' + $af.name); issue='Missing MySQL field'; impact='Crystal/query cannot bind this field'; action='Add MySQL column using Access name' }
        }
    }
    foreach ($extra in $unusedExtras) {
        $fieldMap += [pscustomobject]@{ table=$at.name; accessField=''; mysqlField=$extra.name; status='EXTRA_MYSQL_FIELD'; action='Review before drop; leave if web-only' }
    }
}
$tableFixSql.Add('SET FOREIGN_KEY_CHECKS=@OLD_FOREIGN_KEY_CHECKS;')

$viewSql = New-Object System.Collections.Generic.List[string]
$viewSql.Add('-- FITMOTOR Access QueryDefs -> MySQL VIEW compatibility script')
$viewSql.Add('-- Generated: ' + (Get-Date -Format 'yyyy-MM-dd HH:mm:ss zzz'))
$viewSql.Add('-- Best-effort syntax conversion. Review MANUAL_REVIEW blocks before execution.')
$viewSql.Add('')

$queryMap = @()
$queryDeps = @()
$knownObjects = @($userAccessTables.name + $accessQueries.name) | Where-Object { $_ } | Sort-Object -Unique
foreach ($q in $accessQueries | Sort-Object Name) {
    $view = if ($mysqlViews.ContainsKey($q.name)) { $mysqlViews[$q.name] } else { $null }
    $outputAccess = @($q.outputFields | ForEach-Object { $_.name })
    $outputView = if ($view) { @($view.columns | Sort-Object ordinal | ForEach-Object { $_.name }) } else { @() }
    $outputStatus = if (-not $view) {
        'MISSING_VIEW'
    } elseif (($outputAccess -join '|') -ceq ($outputView -join '|')) {
        'OK'
    } else {
        'OUTPUT_MISMATCH'
    }
    $queryMap += [pscustomobject]@{
        accessQuery=$q.name; mysqlView=if($view){$view.name}else{''}; status=$outputStatus; kind=$q.kind; internal=$q.isInternal;
        accessColumns=($outputAccess -join ', '); mysqlColumns=($outputView -join ', '); note=if($q.outputError){'Access output error: '+$q.outputError}else{''}
    }
    if ($outputStatus -ne 'OK' -and -not $q.isInternal) {
        $priorityMismatches += [pscustomobject]@{ object='QUERY_VIEW'; name=$q.name; issue=$outputStatus; impact='Crystal/query compatibility risk'; action='Create/replace view with exact Access output columns' }
    }
    foreach ($obj in $knownObjects) {
        if ($obj -and $obj -ne $q.name -and $q.sql -match ('(?i)(^|[^A-Za-z0-9_])' + [regex]::Escape($obj) + '([^A-Za-z0-9_]|$)')) {
            $queryDeps += [pscustomobject]@{ query=$q.name; dependsOn=$obj; dependencyType=if($accessTableByName.ContainsKey($obj)){'TABLE'}else{'QUERY'} }
        }
    }
    $manual = @()
    if ($q.isInternal) { $manual += 'internal Access form/report query' }
    if ($q.kind -notin @('SELECT','UNION')) { $manual += "query kind $($q.kind)" }
    if ($q.sql -match '(?i)\bPARAMETERS\b|\bTRANSFORM\b|\bPIVOT\b|\bCROSSTAB\b|\bFORM[S]?!|\bREPORT[S]?!|#\d{1,2}/\d{1,2}/\d{2,4}#|&') {
        $manual += 'Access-specific syntax/reference detected'
    }
    if ($manual.Count -gt 0) {
        $viewSql.Add('-- MANUAL_REVIEW: ' + $q.name + ' (' + ($manual -join '; ') + ')')
        $viewSql.Add('-- ACCESS_SQL: ' + (($q.sql -replace "`r?`n", ' ') -replace '\s+', ' '))
        $viewSql.Add('')
    } else {
        $viewSql.Add('DROP VIEW IF EXISTS ' + (Sql-Ident $q.name) + ';')
        $viewSql.Add('CREATE OR REPLACE VIEW ' + (Sql-Ident $q.name) + ' AS')
        $viewSql.Add((Convert-AccessSqlToMySql $q.sql))
        $viewSql.Add(';')
        $viewSql.Add('')
    }
}

$json = [pscustomobject]@{
    generatedAt = (Get-Date).ToString('s')
    mdbPath = $MdbPath
    mysqlDatabase = $MySqlDatabase
    counts = [pscustomobject]@{
        accessTablesAll = $accessTables.Count
        accessTablesUser = $userAccessTables.Count
        accessQueriesAll = $accessQueries.Count
        mysqlObjects = $mysqlTables.Count
        mysqlViews = $mysqlViews.Count
        crystalReports = 0
    }
    note = 'Detail lengkap ada di Markdown audit dan SQL migration output.'
}

$jsonPath = Join-Path $auditDir 'FITMOTOR_ACCESS_MYSQL_AUDIT_DATA.json'
$mdPath = Join-Path $auditDir 'FITMOTOR_CRYSTAL_ACCESS_MYSQL_AUDIT.md'
$tableSqlPath = Join-Path $migrationDir '2026-06-24_fitmotor_access_table_fix.sql'
$viewSqlPath = Join-Path $migrationDir '2026-06-24_fitmotor_access_views.sql'

$json | ConvertTo-Json -Depth 12 | Set-Content -LiteralPath $jsonPath -Encoding UTF8
$tableFixSql | Set-Content -LiteralPath $tableSqlPath -Encoding UTF8
$viewSql | Set-Content -LiteralPath $viewSqlPath -Encoding UTF8

$md = New-Object System.Collections.Generic.List[string]
$md.Add('# Audit Migrasi FITMOTOR GABUNG: Access vs MySQL untuk Crystal Report')
$md.Add('')
$md.Add('Generated: ' + (Get-Date -Format 'yyyy-MM-dd HH:mm:ss zzz'))
$md.Add('')
$md.Add('Source of truth: `' + $MdbPath + '`')
$md.Add('Target MySQL: `' + $MySqlDatabase + '`')
$md.Add('Fokus audit: integrasi migrasi database secara keseluruhan, tanpa scan report Crystal.')
$md.Add('')
$md.Add('## Ringkasan Temuan')
$md.Add('')
$md.Add('| Item | Jumlah |')
$md.Add('|---|---:|')
$md.Add('| TableDefs Access (semua) | ' + $accessTables.Count + ' |')
$md.Add('| Tabel user Access | ' + $userAccessTables.Count + ' |')
$md.Add('| QueryDefs Access | ' + $accessQueries.Count + ' |')
$md.Add('| Objek MySQL | ' + $mysqlTables.Count + ' |')
$md.Add('| View MySQL | ' + $mysqlViews.Count + ' |')
$md.Add('| File Crystal `.rpt` | 0 |')
$md.Add('| Mismatch prioritas | ' + $priorityMismatches.Count + ' |')
$md.Add('')
$md.Add('Catatan: query Access internal bernama `~sq_...` tetap masuk data audit JSON, tetapi view SQL otomatis hanya dibuat untuk query SELECT/UNION non-internal yang tidak mengandung sintaks Access berisiko.')
$md.Add('')
$md.Add('## A. Mapping Tabel')
$md.Add('')
$md.Add('| Tabel Access | Tabel MySQL | Status | Catatan |')
$md.Add('|---|---|---|---|')
foreach ($row in $tableMap | Sort-Object accessTable) {
    $md.Add('| ' + (Md-Escape $row.accessTable) + ' | ' + (Md-Escape $row.mysqlTable) + ' | ' + $row.status + ' | ' + (Md-Escape $row.note) + ' |')
}
$md.Add('')
$md.Add('## B. Mapping Field Bermasalah')
$md.Add('')
$md.Add('| Tabel | Field Access | Field MySQL Saat Ini | Status | Tindakan |')
$md.Add('|---|---|---|---|---|')
foreach ($row in $fieldMap | Where-Object { $_.status -ne 'OK' } | Sort-Object table, accessField, mysqlField) {
    $md.Add('| ' + (Md-Escape $row.table) + ' | ' + (Md-Escape $row.accessField) + ' | ' + (Md-Escape $row.mysqlField) + ' | ' + $row.status + ' | ' + (Md-Escape $row.action) + ' |')
}
$md.Add('')
$md.Add('## C. Mapping Query/View')
$md.Add('')
$md.Add('| Query Access | View MySQL | Status | Jenis | Internal | Catatan |')
$md.Add('|---|---|---|---|---|---|')
foreach ($row in $queryMap | Sort-Object accessQuery) {
    $md.Add('| ' + (Md-Escape $row.accessQuery) + ' | ' + (Md-Escape $row.mysqlView) + ' | ' + $row.status + ' | ' + $row.kind + ' | ' + $row.internal + ' | ' + (Md-Escape $row.note) + ' |')
}
$md.Add('')
$md.Add('## D. Daftar Mismatch Prioritas')
$md.Add('')
$md.Add('| Objek | Nama | Masalah | Dampak | Tindakan |')
$md.Add('|---|---|---|---|---|')
foreach ($row in $priorityMismatches | Sort-Object object, name) {
    $md.Add('| ' + $row.object + ' | ' + (Md-Escape $row.name) + ' | ' + (Md-Escape $row.issue) + ' | ' + (Md-Escape $row.impact) + ' | ' + (Md-Escape $row.action) + ' |')
}
$md.Add('')
$md.Add('## E. Dependency Query Access')
$md.Add('')
$md.Add('| Query Access | Bergantung Pada | Jenis Dependency |')
$md.Add('|---|---|---|')
foreach ($row in $queryDeps | Sort-Object query, dependsOn) {
    $md.Add('| ' + (Md-Escape $row.query) + ' | ' + (Md-Escape $row.dependsOn) + ' | ' + $row.dependencyType + ' |')
}
$md.Add('')
$md.Add('## F. SQL / Perubahan yang Disiapkan')
$md.Add('')
$md.Add('- SQL fix struktur tabel: `db/migrations/2026-06-24_fitmotor_access_table_fix.sql`')
$md.Add('- SQL create/replace view: `db/migrations/2026-06-24_fitmotor_access_views.sql`')
$md.Add('- Data audit lengkap JSON: `docs/audit/FITMOTOR_ACCESS_MYSQL_AUDIT_DATA.json`')
$md.Add('')
$md.Add('## G. Progress Log')
$md.Add('')
$md.Add('- Sudah diaudit: metadata tabel/field/index Access, query SQL Access, schema tabel/view MySQL, file `.rpt` Crystal.')
$md.Add('- Sudah disiapkan: mapping tabel, mapping field, mapping query/view, dependency query, SQL rekonsiliasi tabel, SQL view best-effort.')
$md.Add('- Masih terbuka: review manual query Access dengan sintaks `PARAMETERS`, `TRANSFORM`, referensi form/report, operator concat `&`, dan query internal `~sq_...`.')
$md.Add('- Validasi berikutnya: jalankan SQL di database staging, lalu ulangi audit untuk memastikan status query/view berubah menjadi `OK` dan output kolom view sama persis.')
$md.Add('')
$md.Add('## H. Open Questions / Risks')
$md.Add('')
$md.Add('- Script rename field memakai heuristik nama mirip; review setiap `LIKELY_RENAME` sebelum eksekusi.')
$md.Add('- Field ekstra MySQL tidak otomatis di-drop karena bisa dipakai modul web baru; tandai dulu sebelum diputuskan.')
$md.Add('- Ekstraksi `.rpt` dilakukan dari string binary, bukan Crystal SDK; report tanpa match tetap perlu dibuka di Crystal untuk melihat data source/formula/group.')

$md | Set-Content -LiteralPath $mdPath -Encoding UTF8

Write-Output "AUDIT_MD=$mdPath"
Write-Output "AUDIT_JSON=$jsonPath"
Write-Output "TABLE_SQL=$tableSqlPath"
Write-Output "VIEW_SQL=$viewSqlPath"
Write-Output ("COUNTS access_user_tables={0} access_queries={1} mysql_objects={2} mysql_views={3} priority_mismatches={4}" -f $userAccessTables.Count,$accessQueries.Count,$mysqlTables.Count,$mysqlViews.Count,$priorityMismatches.Count)

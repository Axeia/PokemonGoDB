<?php
$currentFolder = __DIR__.'/';
spl_autoload_register(function ($className) 
{
    global $currentFolder;
    $sqlParserFolder = $currentFolder.'GenerateSQLiteOpenHelper/';
    if(strpos($className, '\\') !== false)  
    {
        $classPath = str_replace('PHPSQLParser\\', 'PHP-SQL-Parser/src/PHPSQLParser/', $className);
        $classPath = $sqlParserFolder.str_replace('\\', '/', $classPath);
        include $classPath.'.php';
    }
});

include($currentFolder.'GenerateSQLiteOpenHelper/GenerateSQLiteOpenHelper.class.php');
$creates = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.'creates.sql');
$insertPokemonQuery = 'INSERT INTO Pokemon(name, pokedex_number, type1, type2, base_attack, base_defense, base_stamina, base_capture_rate, base_flee_rate, evolves_from, buddy_distance, candy_to_evolve, egg_distance) VALUES ';
$insertTypeQuery = 'INSERT INTO Types VALUES';
$insertPokemonEvolutions = 'INSERT INTO PokemonEvolutions(name, evolved_name) VALUES';
$stringsForAndroid = '';
$uniqueTypes = [];

$rawData = file_get_contents($currentFolder.'decoded_master.protobuf');
$dataParts = explode("item_templates",$rawData);
//The data used is in the protobuf format which sadly cannot be parsed easily in PHP.
//However - using a few regular expressions it can be converted into valid JSON.
for($i = 1; $i < count($dataParts)-1; $i++) //First and last items are of no interest.
{
    $part = $dataParts[$i];
    //Quotes all 'keys' of objects and values that aren't booleans or numbers
    $jsonPart = preg_replace_callback(
        '~([a-z_0-9]+): ([a-zA-Z0-9\._\- :/"]+)~', 
        function($matches)
        {
            $escapedValues = '"'.$matches[1].'": ';
            if(is_numeric($matches[2]) || $matches[2] === 'true' || $matches[2] === 'false')
                $escapedValues .= $matches[2];
            else if(strpos($matches[2], '"') === 0) //Don't quote already quoted values
                $escapedValues .= $matches[2];
            else
                $escapedValues .= '"'.$matches[2].'"';
            
            return $escapedValues.',';
        },
        $part);
    
    //Quote all keys that come before an object and add the needed colon,  e.g.
    //avatar_customization { } becomes "avatar_customization": {} 
    $jsonPart = preg_replace('~([a-z_]+) {~', '"$1": {', $jsonPart);
    //Filter out commas after the last value in an object as that isn't valid JSON.
    $jsonPart = preg_replace('~(,)(\s+})~', '$2', $jsonPart); 
    //Add comma's between object values
    $jsonPart = preg_replace('~}(\s+"[a-z_0-9]+")~', '},$1', $jsonPart);    

    $json = json_decode($jsonPart);
    if($json===null)
    {
        echo '<h1 style="color: red;">Error: '.json_last_error_msg().'</h1>';
        echo '<h2>Whilst parsing input:</h2>';
        echo '<pre>'.$part.'</pre>';
        echo '<h2>as JSONified: </h2>';
        echo '<pre>'.$jsonPart.'</pre>';
        echo '<hr/>';
    }
    else
    {        
        if(isset($json->pokemon_settings))
        {
            $pokemon = [];
            $pokemonSettings = $json->pokemon_settings;
            $stats           = $pokemonSettings->stats;        
            $encounter       = $pokemonSettings->encounter;
            //echo '<pre>'.print_r($json, true).'</pre>';
            //echo extractPokedexNumberFromId($template->templateId).' '.$pokemonSettings->pokemonId."\n";
            
            
            $pokemon['name']           = "'".$pokemonSettings->pokemon_id."'";
            $pokemon['pokedex_number'] = extractPokedexNumberFromId($json->template_id);
            
            $uniqueTypes[$pokemonSettings->type]= '';
            if(isset($pokemonSettings->type_2))
            {
                $uniqueTypes[$pokemonSettings->type_2]= '';
            }
            
            $pokemon['type1'] = "'".$pokemonSettings->type."'";
            $pokemon['type2'] = isset($pokemonSettings->type_2)
                ? "'".$pokemonSettings->type_2."'"
                : $pokemon['type2'] = 'null';
            $pokemon['base_attack']  = $stats->base_attack;
            $pokemon['base_defense'] = $stats->base_defense;
            $pokemon['base_stamina'] = $stats->base_stamina;
            $pokemon['base_capture_rate'] = isset($encounter->base_capture_rate) 
                ? $encounter->base_capture_rate 
                : 'null';
            $pokemon['base_flee_rate'] = $encounter->base_flee_rate;
            $pokemon['evolves_from'] = isset($pokemonSettings->parent_pokemon_id)
                ? "'".$pokemonSettings->parent_pokemon_id."'"
                : 'null';    
            $pokemon['buddy_distance'] = $pokemonSettings->km_buddy_distance;
            $pokemon['candy_to_evolve'] = isset($pokemonSettings->candy_to_evolve)
                ? $pokemonSettings->candy_to_evolve
                : $pokemon['candy_to_evolve'] = 'null';
            $pokemon['egg_distance'] = 'null';
            
            $insertPokemonQuery .= "\n(".implode($pokemon, ', ').'),';
            

            if(isset($pokemonSettings->evolution_ids))
            {
                /*foreach($pokemonSettings->evolution_ids as $evolutionId)
                {
                    $insertPokemonEvolutions .= "\n(";
                    $insertPokemonEvolutions .= "'".$pokemonSettings->pokemonId."', ".
                        "'".$evolutionId."'";
                    $insertPokemonEvolutions .= '),';
                }*/
            }
            
            //echo '<pre>'.print_r($json, true).'</pre>';
            $stringsForAndroid .= htmlspecialchars( 
                sprintf('<string name="%s">%s</string>'."\n", 
                    $pokemonSettings->pokemon_id, 
                    getEnglishPokemonName($pokemonSettings->pokemon_id)
                )
            );
        
        }        
    }
}
//$data = json_decode($rawData);
//die('<pre>'.print_r($data, true).'</pre>');
$sqliteOpenHelper = new GenerateSQLiteOpenHelper($creates, 'PogoDB');

//die();
$insertPokemonQuery = rtrim($insertPokemonQuery, ',').';';
$insertPokemonEvolutions = rtrim($insertPokemonEvolutions, ',').';';

/** @param String */
function extractPokedexNumberFromId($str)
{
    return intval(substr($str, 1, 4), 10);
}

function getEnglishPokemonName($idName)
{
    switch($idName)
    {
        case 'NIDORAN_FEMALE':
            return 'Nidoran♀';
            break;
        case 'NIDORAN_MALE':
            return 'Nidoran♂';
            break;
        case 'MR_MIME':
            return 'Mr.Mime';
            break;
        case 'HO_OH':
            return 'Ho-Oh';
            break;
        default:            
            return str_replace('_', '.', ucfirst(strtolower($idName)));
    }
}

$insertTypeQuery .= "\n('"
    .implode(array_keys($uniqueTypes), "'),\n('")
    .'\');';
?><!doctype html>
<html class="no-js" lang="">
    <head>
        <meta charset="utf-8">
        <meta http-equiv="x-ua-compatible" content="ie=edge">
        <title>SQLiteOpenHelper helper</title>
        <meta name="description" content="">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style>
            html, body{
                margin: 0; 
                padding: 0;
            }
            body {
              background-color: rgb(32, 32, 32);
              background-image: linear-gradient(45deg, black 25%, transparent 25%, transparent 75%, black 75%, black), 
                linear-gradient(45deg, black 25%, transparent 25%, transparent 75%, black 75%, black), 
                linear-gradient(to bottom, rgb(8, 8, 8), rgb(32, 32, 32));
              background-size: 10px 10px, 10px 10px, 10px 5px;
              background-position: 0px 0px, 5px 5px, 0px 0px;
              color: #ddd;
              font-family: "SF Pro Text","SF Pro Icons","Helvetica Neue","Helvetica","Arial",sans-serif;
              padding: 1% 2%;
            }
            h1, h2{
                letter-spacing: 1px;
                text-shadow: 0px 0px 3px rgba(255, 255, 255, 0.5);
                margin: 0;
                padding: 0;
            }
            h1{
                padding: 0;
            }            
            pre{
                width: 100%;
                min-height: 500px;
            }            
            .sql{
                overflow: auto;
                border: 1px #cecece solid ;
            }
            .creates{
            }
            abbr{
                cursor: help;
            }
            input, textarea{
                background: #272822;
                border: 0;
            }
            a{
                color: #e6db74;
            }
            table {
                margin-bottom: 3em;
            }
            table th{                
                white-space: nowrap;
                text-align: right;
            }
            table td{
                width: 90%;
            }
            table td[rowspan="2"]{
                width: 100px;
                height: 2.4em;
            }
            table button{
                display: inline-block;
                height: 2.8em;
                width: 100%;
                height: 100%;
                margin-top: -1px;
                border: 1px solid #fff;
                font-weight: bold;
                background: #559A0D;
                color: #fff;
            }
            table input[type="text"]{
                width: 100%;
                height: 1.4em;
                border: 1px solid #2F3129;
                color: #fff;
            }
            p{
                margin: 0.2em 0;
                padding: 0.4em;
                font-size: 0.9em;
            }
            #credits{
                clear: both;
                bottom: 0.2em;
                margin: 0 auto;
                text-align: center;
            }
            .splitter-container+h1{
                padding-top: 1em;
                clear: both;
            }            
            @media (min-width:1400px) { 
                .splitter-container{ width: 48%; float: left; }
                .splitter-container+.splitter-container{ float: right; }
                .ace_editor, textarea{
                    height: 350px;
                    height: calc(50vh - 250px);
                    width: 100%;
                    color: #e6db74;
                }
            } 
        </style>
    </head>
    <body>
        <h1>Pokémon Go Database</h1>
        <hr/>        
        
        <div class="splitter-container">
            <h2>Create Statements <abbr title="Data Definition Language">(DDL)</h2>
            <p>Create the database structure by running these SQL queries</p>
            <textarea class="sql creates" id="ddl-sql" name="ddl-sql"><?php echo $creates ?></textarea>
        </div>
        
        <div class="splitter-container">
            <h2>Insert Statements <abbr title="Data Manipulation Language">(DML)</abbr></h2>
            <p>Populate the SQLite database</p>
            <textarea id="dml-sql">
<?php 
echo $insertTypeQuery."\n\n"; 
echo $insertPokemonQuery."\n\n";
echo $insertPokemonEvolutions."\n\n";
?>
            </textarea>        
        </div>
        
        <h1>Android Specific</h1>
        <hr/>
        
        <div class="splitter-container">
            <h2>Android SQLiteOpenHelper</h2>
            <p>Android SQLiteOpenHelper class, just copy&amp;paste</p>
            <textarea id="sqlitehelper"><?php echo $sqliteOpenHelper->getJavaString();?></textarea>
        </div>
        
        <div class="splitter-container">
            <h2>Android Strings (XML)</h2>
            <p>XML to be used in your Android projects Strings xml file</p>
            <textarea id="strings"><?php echo $stringsForAndroid ?></textarea>
        </div>
        
        <p id="credits">
            Makes use of <a href='https://ace.c9.io/'>Ace embeddable editor</a> for syntax highlighting. Also uses the <a href="https://github.com/Axeia/GenerateSQLiteOpenHelper">GenerateSQLiteOpenHelper</a> project which in turn uses <a href='https://github.com/greenlion/PHP-SQL-Parser'>PHP-SQL-Parser</a>.
            Enjoy!
        </p>
        <script src="https://cdn.jsdelivr.net/ace/1.2.6/min/ace.js"></script>
        <script>
            var editorDDL = ace.edit("ddl-sql");
            editorDDL.setTheme("ace/theme/monokai");
            editorDDL.getSession().setMode("ace/mode/sql");
            
            var editorDDL = ace.edit("dml-sql");
            editorDDL.setTheme("ace/theme/monokai");
            editorDDL.getSession().setMode("ace/mode/sql");
            
            var editorSQLiteHelper = ace.edit("sqlitehelper");
            editorSQLiteHelper.setTheme("ace/theme/monokai");
            editorSQLiteHelper.getSession().setMode("ace/mode/java");    
            
            var editorSQLiteHelper = ace.edit("strings");
            editorSQLiteHelper.setTheme("ace/theme/monokai");
            editorSQLiteHelper.getSession().setMode("ace/mode/xml");          
        </script>
    </body>
</html>
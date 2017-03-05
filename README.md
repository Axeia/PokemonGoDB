# PokemonGoDB
PokemonGoDB is an attempt at turning the gamedata of interest from Pokémon Go into a SQLite database and a matching Android SQLiteOpenHelper class.
# What's the aim of this project?
For my own use for now the aim is to have a database with the data in it that can be used to retrieve names based on pokédex number, get type information, calculate moveset dps etc etc.
# How does it work?
The [pogo-game-master-decoder](https://github.com/apavlinovic/pogo-game-master-decoder) project is at the base of the project although that's a completely seperate project not made or maintained by me. It takes the games master file and converts it into the [protobuf format](https://developers.google.com/protocol-buffers/)
  1. A datafile as ouput by by pogo-game-master-decoder is converted to JSON using several regular expressions.
  2. SQL insert statements are created based on the JSON file generated in step 1.
  3. An Android SQLiteOpenHelper class is generated based of the create statements.
    * The [GenerateSQLiteOpenHelper](https://github.com/Axeia/GenerateSQLiteOpenHelper) project is used for this, that project is maintained and created by me. 
    It's simply split off from this one as I thought more people have an use for it.
  4. XML string elements are generated based off the JSON to be used in your Android projects strings file containing the names of the Pokémon.
    * The SQL create statement has a pure SQL alternative to get (English) pokémon names. 

/* If you want to get types as they are in the game you can use a query like:
SELECT upper(substr(type, 14, 1)) || lower(substr(type, 15)) 
         AS formatted_type 
	   FROM types
Which will give your the type as they are in the game, like "grass" and "water" 
*/
CREATE TABLE Types
(
  typing VARCHAR(255) PRIMARY KEY NOT NULL
);


/* If you want to select the english name directly from the database
 instead of using the android string example down below you can do so with:
 SELECT 
	CASE name 
	  WHEN 'NIDORAN_FEMALE' THEN 'Nidoran♀'
	  WHEN 'NIDORAN_MALE' THEN 'Nidoran♂'
	  WHEN 'MR_MIME' THEN 'Mr.Mime'
	  WHEN 'HO_OH' THEN 'Ho-Oh'
	ELSE upper(substr(name, 1, 1)) || lower(substr(name, 2))	
	END AS en_name
FROM Pokemon
 */
CREATE TABLE Pokemon
(
  name              VARCHAR(255) NOT NULL,
  pokedex_number    INT          UNIQUE NOT NULL,
  type1             VARCHAR(255) NOT NULL,
  type2             VARCHAR(255),
  base_attack       INT          NOT NULL,
  base_defense      INT          NOT NULL,
  base_stamina      INT          NOT NULL,
  base_capture_rate DOUBLE,
  base_flee_rate    DOUBLE       NOT NULL,
  evolves_from      VARCHAR(255),
  buddy_distance    INT,
  candy_to_evolve   INT,
  egg_distance      INT,
  PRIMARY KEY (name),
  FOREIGN KEY (evolves_from) REFERENCES Pokemon (pokedex_number),
  FOREIGN KEY (type1) REFERENCES Types (typing),
  FOREIGN KEY (type2) REFERENCES Types (typing)
);


/* Some Pokémon have multiple evolutions, like for example Eevee
 (Jolteon, Flareon etc) thus a link table is needed. Want a count of them? 
   SELECT Pokemon.name, COUNT(PokemonEvolutions.name) AS 'number of evolutions'
    FROM Pokemon
	JOIN PokemonEvolutions
	  ON Pokemon.name = PokemonEvolutions.name
GROUP BY Pokemon.name
 */
CREATE TABLE PokemonEvolutions
(
  name            VARCHAR(255) NOT NULL,
  evolved_name    VARCHAR(255) NOT NULL,
  PRIMARY KEY (name, evolved_name),
  FOREIGN KEY(name) REFERENCES Pokemon(name),
  FOREIGN KEY(evolved_name) REFERENCES Pokemon(name)
);
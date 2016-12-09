-- This function will return the first geometry, if the argument is a multi-something
-- or the original geometry if it is not. 
-- Mostly intended to be used in cases where you have a MULTIPOINT with a single point inside it

DELIMITER $$

DROP FUNCTION IF EXISTS wp_first_geom$$

CREATE FUNCTION wp_first_geom(p GEOMETRY) RETURNS GEOMETRY

NO SQL DETERMINISTIC

COMMENT 'Given a starting point, bearing, and distance in meters, create a linestring'

BEGIN
	IF ( GeometryType( p ) = 'MULTIPOINT' || GeometryType( p ) = 'MULTILINESTRING' || GeometryType( p ) = 'MULTIPOLYGON' || GeometryType( p ) = 'GEOMETRYCOLLECTION' ) THEN
		RETURN GeometryN( p, 1 );
	END IF;

	RETURN p;
END$$

/*BaseWord Simple*/
SELECT w.`ID`,w.`UserID`,w.`ListID`, w.`Word`, w.`Translation`, w.`Description`, w.`InsertTime`
FROM Words w
INNER JOIN
(
  SELECT `UserID`, REPLACE(LOWER(Word), ' ', '') AS BaseWord, COUNT(REPLACE(LOWER(Word), ' ', '')) AS Counts
  FROM `Words` 
  GROUP BY `UserID`, REPLACE(Word, ' ', '')
  HAVING COUNT(REPLACE(Word, ' ', '')) > 1
) AS dups
	ON dups.BaseWord = REPLACE(w.Word, ' ', '') AND w.UserID = dups.UserID

/*BaseWord Complete*/
SELECT w.`ID`,w.`UserID`,w.`ListID`, w.`Word`, w.`Translation`, w.`Description`, w.`InsertTime`, dups.BaseWord
FROM Words w
INNER JOIN
(
  SELECT `UserID`
  ,REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(Word), ' ', ''),  '+', ''), ',',''),'/', ''), '''', ''), '-','') AS BaseWord
  ,COUNT(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(Word), ' ', ''),  '+', ''), ',',''),'/', ''), '''', ''), '-','')) AS Counts
  FROM `Words` 
  GROUP BY `UserID`, REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(Word), ' ', ''),  '+', ''), ',',''),'/', ''), '''', ''), '-','')
  HAVING COUNT(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(Word), ' ', ''),  '+', ''), ',',''),'/', ''), '''', ''), '-','')) > 1
) AS dups
	ON dups.BaseWord = REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(LOWER(w.Word), ' ', ''),  '+', ''), ',',''),'/', ''), '''', ''), '-','') AND w.UserID = dups.UserID

SELECT w.* 
FROM `Words` w
INNER JOIN UserList ul ON ul.ListID=w.ListID AND ul.UserID=1
WHERE `WordBase` LIKE 'Harvinainen'

--Duplicate detection
SELECT d.UserID, d.Word, MIN(d.Step) AS MinStep
FROM
(
    SELECT w.`ID`, w.`UserID`, w.`Word`,
    CASE WHEN uws.Step IS NULL THEN 1 ELSE uws.Step END AS Step
    FROM Words AS w
    INNER JOIN
    (    
        SELECT UserID, WordBase, COUNT(ID) AS dup
        FROM Words
        GROUP BY UserID, WordBase
        HAVING COUNT(ID) > 1
    ) AS dups
        ON w.`UserID`= dups.UserID AND w.WordBase=dups.WordBase
    LEFT OUTER JOIN UserWordStep AS uws ON (uws.WordID=w.ID AND uws.UserID=w.UserID)
) AS d
GROUP BY d.UserID, d.Word
ORDER BY d.Word
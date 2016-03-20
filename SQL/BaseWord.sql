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
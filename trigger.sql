DELIMITER ;;
CREATE TRIGGER `InsertUUIDPass` 
BEFORE INSERT ON `UserInfo` 
FOR EACH ROW 
BEGIN 
	DECLARE uid VARCHAR(32);
	SET uid = REPLACE(UUID(),'-','');
	SET NEW.Identifier = uid;
	SET NEW.password = MD5(NEW.password + uid + NEW.passwordsalt);
	SET NEW.passwordsalt = NULL;
END
;;
DELIMITER ;;
CREATE TRIGGER `UpdateCheckTime` 
BEFORE UPDATE ON `UserWordStep`
FOR EACH ROW 
BEGIN 
	SET NEW.`LastCheckTime` = CURRENT_TIMESTAMP;
END
;;

DELIMITER ;;
CREATE TRIGGER `SetDefaultList` 
AFTER UPDATE ON `UserInfo`
FOR EACH ROW 
BEGIN 
  IF NEW.Activated=1 THEN
    DECLARE listID INT;
    INSERT INTO `List`(`ListName`) VALUES ('Default');
    SELECT LAST_INSERT_ID() INTO listID;
    INSERT INTO `UserList`(`ListID`, `UserID`) VALUES (listID, NEW.ID);
  END IF;
END
;;




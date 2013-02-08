Configure the bundle to listen on event like this

Create an event inherit from Event/TriggerEvent


gorg_replication_trigger:
    pdo_connections:
        platal:
            dsn:      %g6dat_db_dsn%
            user:     %g6dat_db_user%
            password: %g6dat_db_password%
    trigger:
        pla_accounts:
            entityManager: platal
            type: pdoSingleRaw
            event: "gram.account.change"
            config:
                new: "INSERT INTO accounts (hruid, type, is_admin, state, full_name, directory_name, display_name, lastname, firstname, sex, password, email, registration_date) VALUES  (:hruid, 'gadz', 0, :enable, CONCAT_WS(' ',:firstname, :lastname), CONCAT_WS(' ', :lastname, :firstname), :firstname, :lastname, :firstname, 'male', :password, :email, NOW())"
                update: "UPDATE accounts SET state=:enable, full_name=CONCAT_WS(' ',:firstname, :lastname), directory_name=CONCAT_WS(' ', :lastname, :firstname), firstname=firstname, lastname=:lastname, password=:password, email=:email WHERE hruid=:hruid"
                remove: "DELETE FROM accounts WHERE hruid=:hruid"
                mapping:
                    hruid: hruid
                    password: password
                    firstname: firstname
                    lastname: lastname
                    enable: enable
                    email: email


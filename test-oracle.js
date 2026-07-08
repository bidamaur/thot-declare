import oracledb from "oracledb";

async function testConnection() {
    let connection;

    try {
        connection = await oracledb.getConnection({
            user: "C##dbprod",
            password: "dbprod",
            connectString: "localhost:1521/XE",
        });

        console.log("✅ Connexion Oracle réussie !");

        const result = await connection.execute(`
            SELECT
                USER AS CURRENT_USER,
                SYSDATE AS CURRENT_DATE
            FROM DUAL
        `);

        console.log(result.rows);
    } catch (err) {
        console.error("❌ Erreur :");
        console.error(err);
    } finally {
        if (connection) {
            await connection.close();
        }
    }
}

testConnection();

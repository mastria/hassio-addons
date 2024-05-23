<!DOCTYPE html>
<html>

<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    <style>
        @font-face {
            font-family: "M PLUS 1p";
            font-style: normal;
            font-weight: 400;
            src: url("../storage/fonts/MPLUS1p-Regular.ttf") format("truetype");
        }

        @font-face {
            font-family: "M PLUS 1p";
            font-style: bold;
            src: url("../storage/fonts/MPLUS1p-Bold.ttf") format("truetype");
        }

        body {
            font-family: "M PLUS 1p";
        }

        table {
            margin-top: 30px;
            width: 100%;
        }

        h1 {
            font-weight: normal;
            margin-bottom: 0;
        }

        hr {
            margin-top: 0;
            /* border-top: 1px solid #c5d1d2; */
            border: 0;
            height: 2px;
            background: #c5d1d2;
        }


        td {
            /* border-bottom: 1px solid #d7d7d7; */
            padding-top: 20px;
            padding-bottom: 2px;
            font-size: 1.3em;
        }

        td.border:after {
            content: "";
            background: #d7d7d7;
            height: 2px;
            width: 92%;
            margin-left: 8%;

            display: block;
        }

        input[type="checkbox"] {
            width: 20px;
            height: 20px;
            margin: 0;
            padding: 0;
            outline: 2px solid #d7d7d7;
            margin-bottom: -3px;
            margin-right: 5px;
        }

        input[type="checkbox"]::before {
            content: "";
        }
    </style>
</head>

<body>
    <h1>LISTA DE COMPRAS</h1>
    <hr>
    <table>
        <tbody>
            <tr>
                <td class="border" width="48%"><input type="checkbox"> Arroz</td>
                <td width="4%"></td>
                <td class="border"><input type="checkbox"> Batata</td>
            </tr>
            <tr>
                <td class="border" width="48%"><input type="checkbox"> Arroz</td>
                <td width="4%"></td>
                <td class="border"><input type="checkbox"> Batata</td>
            </tr>
            <tr>
                <td class="border" width="48%"><input type="checkbox"> Arroz</td>
                <td width="4%"></td>
                <td class="border"><input type="checkbox"> Batata</td>
            </tr>
            <tr>
                <td class="border" width="48%"><input type="checkbox"> Arroz</td>
                <td width="4%"></td>
                <td class="border"><input type="checkbox"> Batata</td>
            </tr>
            <tr>
                <td class="border" width="48%"><input type="checkbox"> Arroz</td>
                <td width="4%"></td>
                <td class="border"><input type="checkbox"> Batata</td>
            </tr>
            <tr>
                <td class="border" width="48%"><input type="checkbox"> Arroz</td>
                <td width="4%"></td>
                <td class="border"><input type="checkbox"> Batata</td>
            </tr>
        </tbody>
    </table>
</body>

</html>

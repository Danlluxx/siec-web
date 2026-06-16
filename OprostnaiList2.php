<!DOCTYPE html>
<html lang="ru">
  <head>
    <meta charset="UTF-8" />
    <title>Опросный лист: Редукционно-охладительная установка</title>
    <style>
      :root {
        --accent: #f14b17;
        --accent-dark: #d64716;
        --bg-start: #f7f8fa;
        --bg-end: #eceff3;
        --surface: rgba(255, 255, 255, 0.97);
        --surface-soft: #f8fafc;
        --line: #d9dee6;
        --line-strong: #c8ced7;
        --text: #2d3138;
        --muted: #69707a;
        --header: #343942;
      }

      body {
        font-family: Arial, sans-serif;
        margin: 0;
        padding: 32px 20px;
        background: linear-gradient(180deg, var(--bg-start) 0%, var(--bg-end) 100%);
        color: var(--text);
      }
      .page-header {
        max-width: 1200px;
        margin: 0 auto 24px;
        display: grid;
        grid-template-columns: 120px minmax(0, 1fr) 120px;
        align-items: center;
        gap: 20px;
      }
      .brand-mark {
        width: 120px;
        height: 96px;
        display: flex;
        align-items: center;
        justify-content: center;
      }
      .brand-mark img {
        display: block;
        width: 86px;
        height: auto;
        object-fit: contain;
      }
      .page-header-spacer {
        width: 120px;
        height: 1px;
      }
      .title-wrap {
        display: flex;
        justify-content: center;
      }
      h1 {
        text-align: center;
        color: var(--text);
        margin: 0;
        font-size: 34px;
        line-height: 1.15;
        position: relative;
        padding-bottom: 16px;
        width: fit-content;
        max-width: 100%;
      }
      h1::after {
        content: "";
        display: block;
        width: 100%;
        height: 4px;
        margin: 14px 0 0;
        border-radius: 999px;
        background: var(--accent);
      }
      form {
        max-width: 1200px;
        margin: 0 auto;
        background: var(--surface);
        padding: 28px 28px 32px;
        border-radius: 18px;
        border: 1px solid rgba(255, 255, 255, 0.75);
        box-shadow: 0 20px 45px rgba(28, 35, 45, 0.09), 0 6px 20px rgba(28, 35, 45, 0.06);
        position: relative;
        overflow: hidden;
      }
      form::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 5px;
        background: linear-gradient(90deg, var(--accent) 0%, #ff7a4d 100%);
      }
      table {
        border-collapse: separate;
        border-spacing: 0;
        width: 100%;
        background: #fff;
        border: 1px solid var(--line);
        border-radius: 16px;
        overflow: hidden;
      }
      th,
      td {
        border-right: 1px solid var(--line);
        border-bottom: 1px solid var(--line);
        padding: 12px 14px;
        vertical-align: top;
        background: rgba(255, 255, 255, 0.96);
      }
      tr:last-child td {
        border-bottom: none;
      }
      th:last-child,
      td:last-child {
        border-right: none;
      }
      th {
        background: linear-gradient(180deg, #40454e 0%, var(--header) 100%);
        color: #fff;
        text-align: center;
        font-weight: 700;
        letter-spacing: 0.02em;
      }
      input[type="text"],
      input[type="number"],
      select,
      textarea {
        width: 100%;
        box-sizing: border-box;
        padding: 11px 12px;
        margin: 2px 0;
        border: 1px solid var(--line-strong);
        border-radius: 10px;
        background: #fff;
        color: var(--text);
        transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
      }

      .top-fields {
        display: flex;
        gap: 18px;
        margin-bottom: 24px;
        flex-wrap: wrap;
      }

      .top-fields > div {
        flex: 1;
        min-width: 200px;
        background: var(--surface-soft);
        border: 1px solid var(--line);
        border-radius: 14px;
        padding: 16px 16px 14px;
        box-sizing: border-box;
      }

      label {
        display: block;
        font-size: 0.92em;
        margin-bottom: 6px;
        color: var(--muted);
        font-weight: 700;
      }

      input[type="text"],
      input[type="number"],
      input[type="email"],
      select {
        width: 100%;
        padding: 11px 12px;
        margin: 2px 0;
        box-sizing: border-box;
        border: 1px solid var(--line-strong);
        border-radius: 10px;
        background: #fff;
        color: var(--text);
      }
      input[type="text"]:focus,
      input[type="number"]:focus,
      input[type="email"]:focus,
      select:focus,
      textarea:focus {
        outline: none;
        border-color: var(--accent);
        box-shadow: 0 0 0 3px rgba(241, 75, 23, 0.14);
        background: #fffdfa;
      }
      input[type="checkbox"],
      input[type="radio"] {
        margin-right: 6px;
        accent-color: var(--accent);
      }
      textarea {
        resize: vertical;
        min-height: 90px;
      }
      .yes-no {
        display: flex;
        gap: 20px;
        flex-wrap: wrap;
      }
      .yes-no label {
        font-weight: normal;
        margin-bottom: 0;
      }
      .section-title {
        background-color: #fff4ed;
        font-weight: bold;
        text-align: left;
        color: var(--text);
      }
      input[type="submit"] {
        margin-top: 28px;
        padding: 16px 28px;
        min-width: 290px;
        background-color: var(--accent);
        color: white;
        border: none;
        border-radius: 10px;
        cursor: pointer;
        display: block;
        margin-left: auto;
        margin-right: auto;
        font-size: 16px;
        font-weight: 700;
        letter-spacing: 0.02em;
        box-shadow: 0 14px 28px rgba(241, 75, 23, 0.22);
        transition: transform 0.2s ease, background-color 0.2s ease, box-shadow 0.2s ease;
      }
      input[type="submit"]:hover {
        background-color: var(--accent-dark);
        transform: translateY(-1px);
        box-shadow: 0 16px 30px rgba(241, 75, 23, 0.26);
      }
      input[type="submit"]:active {
        transform: translateY(0);
      }
      tbody tr:hover td {
        background-color: #fffefd;
      }
      @media screen and (max-width: 768px) {
        body {
          padding: 20px 12px;
        }
        .page-header {
          grid-template-columns: 1fr;
          gap: 16px;
          margin-bottom: 20px;
        }
        .brand-mark {
          width: 96px;
          height: 76px;
          margin: 0 auto;
        }
        .page-header-spacer {
          display: none;
        }
        h1 {
          font-size: 28px;
          margin: 0 auto;
        }
        form {
          padding: 20px 14px 24px;
          border-radius: 14px;
        }
        th,
        td {
          padding: 10px 8px;
          font-size: 14px;
        }
        .yes-no {
          flex-direction: column;
          gap: 12px;
        }
        input[type="submit"] {
          width: 100%;
          min-width: 0;
        }
      }
    </style>
  </head>
  <body>
    <div class="page-header">
      <div class="brand-mark">
        <img src="images/siec-logo.png" alt="Логотип Сибирской энергетической компании" />
      </div>
      <div class="title-wrap">
        <h1>Опросный лист: Редукционно-охладительная установка</h1>
      </div>
      <div class="page-header-spacer" aria-hidden="true"></div>
    </div>

    <form action="send2.php" method="post" class="js-questionnaire-form">
      <div class="top-fields">
        <div>
          <label for="org">Название организации</label>
          <input type="text" name="organization" id="org" required="" />
        </div>
        <div>
          <label for="email">E-mail</label>
          <input type="email" name="email" id="email" required="" />
        </div>
        <div>
          <label for="phone">Номер телефона</label>
          <input type="text" name="phone" id="phone" required="" />
        </div>
      </div>
      <table>
        <thead>
          <tr>
            <th>№ п/п</th>
            <th>Наименование параметра</th>
            <th>Обозначение</th>
            <th>Единица измерения</th>
            <th>Значение</th>
          </tr>
        </thead>
        <tbody>
          <tr>
            <td>1</td>
            <td>Производительность установки по редуцированному пару</td>
            <td>G</td>
            <td>т/час</td>
            <td><input type="number" name="G" step="any" required /></td>
          </tr>
          <tr>
            <td>2</td>
            <td>Давление острого пара, избыточное</td>
            <td>P1</td>
            <td>МПа</td>
            <td><input type="number" name="P1" step="any" required /></td>
          </tr>
          <tr>
            <td>3</td>
            <td>Давление редуцированного пара, избыточное</td>
            <td>P2</td>
            <td>МПа</td>
            <td><input type="number" name="P2" step="any" required /></td>
          </tr>
          <tr>
            <td>4</td>
            <td>Температура острого пара</td>
            <td>T1</td>
            <td>°C</td>
            <td><input type="number" name="T1" step="any" required /></td>
          </tr>
          <tr>
            <td>5</td>
            <td>Температура редуцированного пара</td>
            <td>T2</td>
            <td>°C</td>
            <td><input type="number" name="T2" step="any" required /></td>
          </tr>
          <tr>
            <td>6</td>
            <td>Давление охлаждающей воды, избыточное</td>
            <td>Pв</td>
            <td>МПа</td>
            <td><input type="number" name="Pv" step="any" required /></td>
          </tr>
          <tr>
            <td>7</td>
            <td>Температура охлаждающей воды</td>
            <td>Tв</td>
            <td>°C</td>
            <td><input type="number" name="Tv" step="any" required /></td>
          </tr>
          <tr>
            <td>8</td>
            <td colspan="3" class="section-title">Исполнение</td>
            <td>
              <select name="execution" required>
                <option value="общепромышленное">общепромышленное</option>
                <option value="экспорт">экспорт</option>
                <option value="тропики">тропики</option>
              </select>
            </td>
          </tr>
          <tr>
            <td>9</td>
            <td colspan="3" class="section-title">
              Объём поставки и требуемая комплектация
            </td>
            <td>
              <label
                ><input type="checkbox" name="kit_inlet_gate" />Задвижка на
                входе</label
              ><br />
              <label
                ><input type="checkbox" name="kit_outlet_gate" />Задвижка на
                выходе</label
              ><br />
              <label
                ><input
                  type="checkbox"
                  name="kit_electro_drive_gate"
                />Электропривод на задвижках</label
              ><br />
              <label
                ><input type="checkbox" name="kit_manual_drive_gate" />Ручной
                привод на задвижке</label
              ><br />
              <label
                ><input
                  type="checkbox"
                  name="kit_electro_drive_ctrl"
                />Электропривод на регулирующих клапанах</label
              ><br />
              <label
                ><input
                  type="checkbox"
                  name="kit_pneumo_drive_ctrl"
                />Пневмопривод на регулирующих клапанах</label
              ><br />
              <label
                ><input type="checkbox" name="kit_check_valve" />Обратный
                клапан</label
              ><br />
              <label
                ><input type="checkbox" name="kit_drainage" />Дренажная
                система</label
              ><br />
              <label
                ><input type="checkbox" name="kit_automation" />Автоматика и
                КИП</label
              >
            </td>
          </tr>
          <tr>
            <td>10</td>
            <td colspan="3">Особые требования</td>
            <td><textarea name="special_requirements"></textarea></td>
          </tr>
          <tr>
            <td>11</td>
            <td colspan="3" class="section-title">Диаметр трубопровода</td>
            <td>
              <label
                >На входе:
                <input type="text" name="D_in" placeholder="DxS" /></label
              ><br />
              <label
                >На выходе:
                <input type="text" name="D_out" placeholder="DxS" /></label
              ><br />
              <label
                >Охлаждающая вода:
                <input type="text" name="D_cw" placeholder="DxS"
              /></label>
            </td>
          </tr>
          <tr>
            <td>12</td>
            <td colspan="3">Тип присоединения</td>
            <td class="yes-no">
              <label
                ><input
                  type="radio"
                  name="conn_type"
                  value="приварка"
                  required
                />под приварку</label
              >
              <label
                ><input type="radio" name="conn_type" value="фланцы" />на
                фланцах</label
              >
              <label
                ><input type="radio" name="conn_type" value="не важно" />не
                важно</label
              >
            </td>
          </tr>
          <tr>
            <td>13</td>
            <td colspan="3">РОУ, ОУ, РУ поставляется</td>
            <td class="yes-no">
              <label
                ><input
                  type="radio"
                  name="supply_type"
                  value="на ед.раме"
                  required
                />на ед.раме</label
              >
              <label
                ><input
                  type="radio"
                  name="supply_type"
                  value="россыпью"
                />россыпью</label
              >
            </td>
          </tr>
        </tbody>
      </table>

      <input type="submit" value="Отправить данные" />
    </form>
    <script>
      document.addEventListener("DOMContentLoaded", function () {
        document.querySelectorAll(".js-questionnaire-form").forEach(function (form) {
          form.addEventListener("submit", function (event) {
            if (!form.checkValidity()) {
              event.preventDefault();
              form.reportValidity();

              var firstInvalid = form.querySelector(":invalid");
              if (firstInvalid) {
                firstInvalid.scrollIntoView({ behavior: "smooth", block: "center" });
                firstInvalid.focus({ preventScroll: true });
              }
            }
          });
        });
      });
    </script>
  </body>
</html>

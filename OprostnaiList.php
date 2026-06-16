<!DOCTYPE html>
<html lang="ru">
<head>
<meta charset="UTF-8">
<title>Опросный лист: Тягодутьевая машина</title>
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

  th, td {
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
    transition: border-color 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
  }

  input[type="text"]:focus,
  input[type="number"]:focus,
  input[type="email"]:focus,
  select:focus {
    outline: none;
    border-color: var(--accent);
    box-shadow: 0 0 0 3px rgba(241, 75, 23, 0.14);
    background: #fffdfa;
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

  .section-title {
    background-color: #fff4ed;
    font-weight: 700;
    text-align: left;
    color: var(--text);
  }

  small {
    display: block;
    margin-top: 6px;
    color: var(--muted);
    line-height: 1.5;
  }

  tbody tr:hover td {
    background-color: #fffefd;
  }

  tbody tr:hover td.section-title {
    background-color: #fff4ed;
  }

  th:nth-child(1),
  td:nth-child(1) {
    width: 8%;
    white-space: nowrap;
  }

  th:nth-child(3),
  td:nth-child(3) {
    width: 16%;
    white-space: nowrap;
  }

  th:nth-child(4),
  td:nth-child(4) {
    width: 22%;
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
    <img src="images/siec-logo.png" alt="Логотип Сибирской энергетической компании">
  </div>
  <div class="title-wrap">
    <h1>Опросный лист: Тягодутьевая машина</h1>
  </div>
  <div class="page-header-spacer" aria-hidden="true"></div>
</div>

<form action="send.php" method="post" class="js-questionnaire-form">
	
<div class="top-fields">
  <div>
    <label for="org">Название организации</label>
    <input type="text" name="organization" id="org" required>
  </div>
  <div>
    <label for="email">E-mail</label>
    <input type="email" name="email" id="email" required>
  </div>
  <div>
    <label for="phone">Номер телефона</label>
    <input type="text" name="phone" id="phone" required>
  </div>
</div>

<table>
<thead>
<tr>
<th>№ п/п</th>
<th>Наименование параметра</th>
<th>Единица измерения</th>
<th>Значение</th>
</tr>
</thead>

<tbody>

<tr>
<td>1</td>
<td>Тип тягодутьевой машины</td>
<td></td>
<td><select name="one">
<option value="Осевая">Осевая</option>
<option value="Центорбежная одностороннего всасывания">Центорбежная одностороннего всасывания</option>
<option value="Центорбежная двухстороннего всасывания">Центорбежная двухстороннего всасывания</option>
</select></td>
</tr>

<tr>
<td>2</td>
<td>Вид климатического исполнения по ГОСТ 15150</td>
<td></td>
<td><select name="two">
<option value="У — для умеренного климата">У — для умеренного климата</option>
<option value="ХЛ — для холодного климата">ХЛ — для холодного климата</option>
<option value="ТВ — для влажного тропического климата">ТВ — для влажного тропического климата</option>
<option value="ТС — для тропического сухого климата">ТС — для тропического сухого климата</option>
<option value="Т — для тропического как сухого, так и влажного климата">Т — для тропического как сухого, так и влажного климата</option>
<option value="О — для всех районов на суше">О — для всех районов на суше</option>
<option value="М — для морского умеренного холодного климата">М — для морского умеренного холодного климата</option>
<option value="ТМ — для тропического морского климата">ТМ — для тропического морского климата</option>
<option value="ОМ — для неограниченного района плавания">ОМ — для неограниченного района плавания</option>
<option value="В — для всех районов на суше и море">В — для всех районов на суше и море</option>
</select></td>
</tr>

<tr>
<td>3</td>
<td>Угол разворота спирального корпуса<small>Примечание: угол отсчитывается от горизонтальной плоскости в сторону вращения рабочего колеса, если смотреть со стороны привода</small></td>
<td>градус</td>
<td><input type="number" name="three"></td>
</tr>

<tr>
<td>3.1</td>
<td>Угол разворота всасывающего кармана</td>
<td>градус</td>
<td><input type="number" name="three_one"></td>
</tr>

<tr>
<td>4</td>
<td>Количество машин</td>
<td>шт.</td>
<td><input type="number" name="four"></td>
</tr>

<tr>
<td>4.1</td>
<td>Направление вращения рабочего колеса<small>Примечание: правое — по часовой стрелке, левое — против, если смотреть со стороны привода</small></td>
<td></td>
<td><input type="text" name="four_one"></td>
</tr>

<tr>
<td>5</td>
<td>Назначение машины (вентилятор, дымосос) и наименование агрегата, для которого она применяется</td>
<td></td>
<td><input type="text" name="five"></td>
</tr>

<tr>
<td>6</td>
<td>Требования к экономичности — желательный максимальный КПД</td>
<td>%</td>
<td><input type="number" name="six"></td>
</tr>

<tr>
<td>7</td>
<td colspan="3" class="section-title">Характеристика перемещаемой среды при нормальных условиях (температура °C и давление 1013 ГПа (760 мм рт.ст.))</td>
</tr>

<tr>
<td>7.1</td>
<td>Плотность</td>
<td>кг/м³</td>
<td><input type="number" name="seven_one"></td>
</tr>

<tr>
<td>7.2</td>
<td>Концентрация твердых примесей</td>
<td>г/м³</td>
<td><input type="number" name="seven_two"></td>
</tr>

<tr>
<td>8</td>
<td colspan="3" class="section-title">Расчетные параметры</td>
</tr>

<tr>
<td>8.1</td>
<td>Температура перемещаемой среды</td>
<td>°C</td>
<td><input type="number" name="eight_one"></td>
</tr>
<tr>
<td>8.2</td>
<td>Избыточное статическое давление (+) или разряженное (-) на входе в машину</td>
<td>Па (кгс/м²)</td>
<td><input type="number" name="eight_two"></td>
</tr>

<tr>
<td>8.3</td>
<td>Барометрическое давление окружающей среды в месте установки ТДМ</td>
<td>ГПа (мм рт.ст.)</td>
<td><input type="number" name="eight_three"></td>
</tr>

<tr>
<td>8.4</td>
<td>Производительность с учетом пп. 8.1, 8.2</td>
<td>м³/ч</td>
<td><input type="number" name="eight_four"></td>
</tr>

<tr>
<td>8.5</td>
<td>Полное давление с учетом пп. 7.1, 8.1, 8.2 (при производительности по п. 8.4)</td>
<td>Па (кгс/м²)</td>
<td><input type="number" name="eight_five"></td>
</tr>

<tr>
<td>8.6</td>
<td>Склонность к отложению примесей на лопатках рабочего колеса</td>
<td></td>
<td><input type="text" name="eight_six"></td>
</tr>

<tr>
<td>8.7</td>
<td>Содержание агрессивных компонентов и рекомендуемая марка материала</td>
<td>%</td>
<td><input type="text" name="eight_seven"></td>
</tr>

<tr>
<td>8.8</td>
<td>Предельная температура перемещаемой среды</td>
<td>°C</td>
<td><input type="number" name="eight_eight"></td>
</tr>

<tr>
<td>8.9</td>
<td>Частота вращения рабочего колеса (желательная)</td>
<td>об/мин</td>
<td><input type="number" name="eight_nine"></td>
</tr>

<tr>
<td>8.10</td>
<td>Необходимость регулирования производительности</td>
<td></td>
<td><input type="text" name="eight_ten"></td>
</tr>

<tr>
<td>9</td>
<td colspan="3" class="section-title">Требования к приводному электродвигателю</td>
</tr>

<tr>
<td>9.1</td>
<td>Тип двигателя</td>
<td></td>
<td><input type="text" name="nine_one"></td>
</tr>

<tr>
<td>9.2</td>
<td>Вид климатического исполнения по ГОСТ 15150</td>
<td></td>
<td><input type="text" name="nine_two"></td>
</tr>

<tr>
<td>9.3</td>
<td>Степень защиты по ГОСТ 17494 или исполнение двигателя</td>
<td></td>
<td><input type="text" name="nine_three"></td>
</tr>

<tr>
<td>9.4</td>
<td>Напряжение сети</td>
<td>В</td>
<td><input type="number" name="nine_four"></td>
</tr>

<tr>
<td>9.5</td>
<td>Частота тока</td>
<td>Гц</td>
<td><input type="number" name="nine_five"></td>
</tr>

<tr>
<td>9.6</td>
<td>Дополнительные требования (режим работы ГОСТ 183, количество пусков и т.д.)</td>
<td></td>
<td><input type="text" name="nine_six"></td>
</tr>

<tr>
<td>10</td>
<td>Ориентировочный срок поставки машины</td>
<td>год</td>
<td><input type="text" name="ten"></td>
</tr>

</tbody>
</table>

<input type="submit" value="Отправить данные">
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

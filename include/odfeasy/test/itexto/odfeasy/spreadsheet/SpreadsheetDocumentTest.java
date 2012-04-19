/*
 * To change this template, choose Tools | Templates
 * and open the template in the editor.
 */

package itexto.odfeasy.spreadsheet;

import java.io.File;
import java.util.List;
import org.junit.After;
import org.junit.AfterClass;
import org.junit.Before;
import org.junit.BeforeClass;
import org.junit.Test;
import static org.junit.Assert.*;

/**
 *
 * @author henriqueloboweissmann
 */
public class SpreadsheetDocumentTest {

    public SpreadsheetDocumentTest() {
    }

    @BeforeClass
    public static void setUpClass() throws Exception {
    }

    @AfterClass
    public static void tearDownClass() throws Exception {
    }

    @Before
    public void setUp() {
    }

    @After
    public void tearDown() {
    }

    @Test
    public void testSetSheet() throws Exception {
        System.out.println("setSheet");
        SpreadsheetDocument document = new SpreadsheetDocument();
        Sheet planilha = new Sheet(document);
        
        document.setSheet(planilha);
        assertNotNull(document.getSheet());
        assertEquals(planilha, document.getSheet());
        
        Table tabela = new Table(planilha);
        
        assertNotNull(tabela.getSheet());
        assertEquals(tabela.getSheet(), planilha);
        tabela.setName("New plan");
        
        Row row = new Row(tabela);
        
        Cell cell = new Cell(row, 0);
        cell.setContent("Just a cell... tsc tsc tsc...");
        
        Cell another = new Cell(row, 3);
        cell.setContent("Second cell");
        cell.setColumn(4);
        document.save(new File("/Users/henriqueloboweissmann/teste.ods"));
        //document.save("~/teste.ods");
    }
    
    
}